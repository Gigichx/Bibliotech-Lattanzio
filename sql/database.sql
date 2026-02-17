-- BiblioTech Database Schema
CREATE DATABASE IF NOT EXISTS bibliotech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bibliotech;

DROP TABLE IF EXISTS prestiti;
DROP TABLE IF EXISTS magic_tokens;
DROP TABLE IF EXISTS libri;
DROP TABLE IF EXISTS utenti;

CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    ruolo ENUM('studente', 'bibliotecario') NOT NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE magic_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    scadenza DATETIME NOT NULL,
    usato TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_utente) REFERENCES utenti(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_scadenza (scadenza),
    INDEX idx_usato (usato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE libri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    autore VARCHAR(150) NOT NULL,
    copie_totali INT NOT NULL DEFAULT 1,
    copie_disponibili INT NOT NULL DEFAULT 1,
    CHECK (copie_disponibili >= 0),
    CHECK (copie_disponibili <= copie_totali),
    INDEX idx_titolo (titolo),
    INDEX idx_autore (autore)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prestiti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    id_libro INT NOT NULL,
    data_prestito DATE NOT NULL,
    data_restituzione DATE NULL,
    FOREIGN KEY (id_utente) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (id_libro) REFERENCES libri(id) ON DELETE CASCADE,
    INDEX idx_utente (id_utente),
    INDEX idx_libro (id_libro),
    INDEX idx_data_restituzione (data_restituzione)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO utenti (nome, email, ruolo) VALUES
('Mario Rossi', 'mario.rossi@example.com', 'studente'),
('Laura Bianchi', 'laura.bianchi@example.com', 'studente'),
('Giuseppe Verdi', 'giuseppe.verdi@example.com', 'studente'),
('Anna Biblioteca', 'anna.biblioteca@example.com', 'bibliotecario');

INSERT INTO libri (titolo, autore, copie_totali, copie_disponibili) VALUES
('Il Nome della Rosa', 'Umberto Eco', 3, 3),
('1984', 'George Orwell', 2, 1),
('Il Signore degli Anelli', 'J.R.R. Tolkien', 4, 4),
('Cent''anni di solitudine', 'Gabriel García Márquez', 1, 0),
('Orgoglio e Pregiudizio', 'Jane Austen', 3, 2);

INSERT INTO prestiti (id_utente, id_libro, data_prestito, data_restituzione) VALUES
(1, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL),
(2, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL),
(3, 4, DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY));