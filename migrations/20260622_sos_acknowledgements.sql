CREATE TABLE sos_acknowledgements (
    id VARCHAR(36) NOT NULL,
    incident_id VARCHAR(36) NOT NULL,
    receiver_user_id VARCHAR(36) NOT NULL,
    acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sos_acknowledgements_receiver (incident_id, receiver_user_id),
    KEY idx_sos_acknowledgements_receiver (receiver_user_id, acknowledged_at),
    CONSTRAINT fk_sos_acknowledgements_incident
        FOREIGN KEY (incident_id) REFERENCES incidents (id) ON DELETE CASCADE,
    CONSTRAINT fk_sos_acknowledgements_receiver
        FOREIGN KEY (receiver_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
