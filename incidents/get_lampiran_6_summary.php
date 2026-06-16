<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/db_connect.php';

$month_offset = isset($_GET['month_offset']) ? intval($_GET['month_offset']) : 0;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : 'daerah'; // default to daerah

$target_timestamp = strtotime("-{$month_offset} months");
$target_month = date('m', $target_timestamp);
$target_year = date('Y', $target_timestamp);

// 1. Resolve User District/Negeri/KRT
$admin_zone = 'Zon Pusat';
$admin_name = 'Admin KRT';

if (!empty($user_id)) {
    $stmt_user = $conn->prepare("SELECT full_name, district_zone FROM users WHERE id = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("s", $user_id);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        if ($res_user->num_rows > 0) {
            $user_row = $res_user->fetch_assoc();
            $admin_name = $user_row['full_name'];
            $admin_zone = $user_row['district_zone'] ?? 'Zon Pusat';
        }
        $stmt_user->close();
    }
}

// Map zones to PPD (Daerah) and PPN (Negeri)
$meta_negeri = "MELAKA";
if ($admin_zone === 'Zon Timur') {
    $meta_daerah = "JASIN";
    $meta_srs = "KRT ZON TIMUR";
} else {
    $meta_daerah = "ALOR GAJAH";
    $meta_srs = "KRT ZON PUSAT";
}

// 2. Define JPNIN 24 Categories Structure
$groups = [
    "A. JENAYAH KEKERASAN" => [
        "1. Pembunuhan" => "Jenayah Kekerasan - Pembunuhan",
        "2. Rogol" => "Jenayah Kekerasan - Rogol",
        "3. Cabul Kehormatan" => "Jenayah Kekerasan - Cabul Kehormatan",
        "4. Samun" => "Jenayah Kekerasan - Samun",
        "5. Peras Ugut" => "Jenayah Kekerasan - Peras Ugut",
        "6. Merusuh" => "Jenayah Kekerasan - Merusuh",
        "7. Mencedera / Penderaan" => "Jenayah Kekerasan - Mencedera / Penderaan"
    ],
    "B. JENAYAH HARTA BENDA" => [
        "1. Curi Kenderaan" => "Jenayah Harta Benda - Curi Kenderaan",
        "2. Curi Harta Benda" => "Jenayah Harta Benda - Curi Harta Benda",
        "3. Pecah Rumah" => "Jenayah Harta Benda - Pecah Rumah"
    ],
    "C. JENAYAH NARKOTIK" => [
        "1. Pengedaran Dadah" => "Jenayah Narkotik - Pengedaran Dadah",
        "2. Memiliki Dadah" => "Jenayah Narkotik - Memiliki Dadah"
    ],
    "D. GEJALA SOSIAL" => [
        "1. Pertengkaran" => "Gejala Sosial - Pertengkaran",
        "2. Lepak" => "Gejala Sosial - Lepak",
        "3. Vandalisme" => "Gejala Sosial - Vandalisme",
        "4. Khalwat / Maksiat" => "Gejala Sosial - Khalwat / Maksiat",
        "5. Pergaduhan" => "Gejala Sosial - Pergaduhan",
        "6. Pendatang Tanpa Izin" => "Gejala Sosial - Pendatang Tanpa Izin",
        "7. Lumba Haram" => "Gejala Sosial - Lumba Haram",
        "8. Penagihan Dadah" => "Gejala Sosial - Penagihan Dadah",
        "9. Masalah Rumah Tangga" => "Gejala Sosial - Masalah Rumah Tangga",
        "10. Ponteng" => "Gejala Sosial - Ponteng",
        "11. Kutu Rayau" => "Gejala Sosial - Kutu Rayau",
        "12. Buang Bayi" => "Gejala Sosial - Buang Bayi",
        "13. Lain-Lain Kes" => "Lain-lain"
    ]
];

// Initialize summary table array
$summary = [];
foreach ($groups as $groupName => $subcats) {
    $summary[$groupName] = [];
    foreach ($subcats as $name => $key) {
        $summary[$groupName][$name] = [
            "total" => 0,
            "polis" => 0,
            "agama" => 0,
            "pbt" => 0,
            "obb" => 0,
            "lain" => 0
        ];
    }
}

// 3. Fetch Incidents
$query = "
    SELECT i.category, i.reported_to, u.district_zone
    FROM incidents i
    JOIN users u ON i.user_id = u.id
    WHERE MONTH(i.created_at) = ? AND YEAR(i.created_at) = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("ss", $target_month, $target_year);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $cat = $row['category'];
    $ref = $row['reported_to'];
    $zone = $row['district_zone'];
    
    // Filter by level
    if ($level === 'krt') {
        if ($zone !== $admin_zone) continue;
    } elseif ($level === 'daerah') {
        // Since admin zone Zon Pusat maps to Alor Gajah, and Zon Timur to Jasin,
        // we filter incidents matching the admin's Resolved Daerah.
        if ($admin_zone === 'Zon Timur' && $zone !== 'Zon Timur') continue;
        if ($admin_zone !== 'Zon Timur' && $zone === 'Zon Timur') continue;
    }
    // 'negeri' and 'kebangsaan' will count all incidents in the database

    // Map agensi referral to 2015 standard columns
    $ref_code = 'lain';
    if ($ref === 'Polis') {
        $ref_code = 'polis';
    } elseif ($ref === 'Jabatan Agama / JAKIM' || $ref === 'Jabatan Agama') {
        $ref_code = 'agama';
    } elseif ($ref === 'PBT') {
        $ref_code = 'pbt';
    } elseif ($ref === 'JKM' || $ref === 'OBB') {
        $ref_code = 'obb'; // JKM is mapped to OBB column
    } elseif ($ref === 'Lain-lain') {
        $ref_code = 'lain';
    } else {
        $ref_code = 'lain';
    }

    // Try to match the incident category
    $matched = false;
    foreach ($groups as $groupName => $subcats) {
        foreach ($subcats as $name => $dbKey) {
            if ($dbKey === $cat) {
                $summary[$groupName][$name]['total']++;
                $summary[$groupName][$name][$ref_code]++;
                $matched = true;
                break 2;
            }
        }
    }

    // If it did not match any category, put under D.13 Lain-Lain Kes
    if (!$matched) {
        $summary["D. GEJALA SOSIAL"]["13. Lain-Lain Kes"]['total']++;
        $summary["D. GEJALA SOSIAL"]["13. Lain-Lain Kes"][$ref_code]++;
    }
}

$month_names_my = ["Januari", "Februari", "Mac", "April", "Mei", "Jun", "Julai", "Ogos", "September", "Oktober", "November", "Disember"];
$month_str = $month_names_my[(int)$target_month - 1];

echo json_encode([
    "success" => true,
    "month_label" => $month_str,
    "year_label" => $target_year,
    "metadata" => [
        "negeri" => $meta_negeri,
        "daerah" => $meta_daerah,
        "nama_srs" => $meta_srs,
        "admin_name" => $admin_name
    ],
    "data" => $summary
]);

$stmt->close();
$conn->close();
?>
