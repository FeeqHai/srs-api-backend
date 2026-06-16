<?php
require_once 'config/db_connect.php';

function createOrUpdateUser($conn, $email, $password, $role, $fullName) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // Update
        $stmt2 = $conn->prepare("UPDATE users SET password_hash = ?, role = ?, account_status = 'active', full_name = ? WHERE email = ?");
        $stmt2->bind_param("ssss", $hash, $role, $fullName, $email);
        $stmt2->execute();
        echo "Updated $email to role $role.\n";
    } else {
        // Insert
        $random_ic = substr(str_shuffle("0123456789"), 0, 12);
        $stmt3 = $conn->prepare("INSERT INTO users (id, full_name, ic_number, district_zone, phone_number, email, password_hash, role, account_status) VALUES (UUID(), ?, ?, 'Zon Pusat', '0123456789', ?, ?, ?, 'active')");
        $stmt3->bind_param("sssss", $fullName, $random_ic, $email, $hash, $role);
        $stmt3->execute();
        echo "Inserted $email with role $role.\n";
    }
}

// Patrollers
createOrUpdateUser($conn, 'patrol1@srs.gov.my', 'password123', 'peronda', 'Ahmad Patroller Satu');
createOrUpdateUser($conn, 'patrol2@srs.gov.my', 'password123', 'peronda', 'Siti Patroller Dua');

// Admin / Ketua KRT
createOrUpdateUser($conn, 'admin@srs.gov.my', 'password123', 'ketua_krt', 'Admin KRT');

// Squad Leader / Ketua Peronda
createOrUpdateUser($conn, 'squad@srs.gov.my', 'password123', 'ketua_peronda', 'Ketua Peronda Zon Pusat');

?>
