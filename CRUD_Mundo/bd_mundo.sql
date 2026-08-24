CREATE DATABASE bd_mundo;
 
USE bd_mundo;
 
CREATE TABLE continentes(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    populacao BIGINT,
    area DECIMAL(15,2),
    total_paises INT
);
 
CREATE TABLE governantes(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    partido_politico VARCHAR(100),
    data_nascimento DATE,
    idade INT,
    inicio_mandato DATE,
    fim_mandato DATE
);
 
CREATE TABLE paises(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    continente_id INT,
    populacao BIGINT,
    area DECIMAL(15,2),
    idioma VARCHAR(100),
    governante_id INT,
    clima VARCHAR(100),
    regime_politico VARCHAR(100),
    moeda VARCHAR(100),
 
    FOREIGN KEY (continente_id)
    REFERENCES continentes(id),
 
    FOREIGN KEY (governante_id)
    REFERENCES governantes(id)
);
 
CREATE TABLE cidades(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    pais_id INT,
    populacao BIGINT,
    area DECIMAL(15,2),
    clima VARCHAR(100),
    governante_id INT,
    data_fundacao DATE,
 
    FOREIGN KEY (pais_id)
    REFERENCES paises(id)
    ON DELETE CASCADE,
 
    FOREIGN KEY (governante_id)
    REFERENCES governantes(id)
);
 
CREATE TABLE usuarios(
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    login VARCHAR(60) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    primeiro_acesso TINYINT(1) NOT NULL DEFAULT 1,
    tentativas_falhas INT NOT NULL DEFAULT 0,
    bloqueado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
 
CREATE TABLE logs(
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    login_tentado VARCHAR(60) NOT NULL,
    sucesso TINYINT(1) NOT NULL,
    mensagem VARCHAR(255),
    ip VARCHAR(45),
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 
    FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id)
    ON DELETE SET NULL
);
 
-- Usuário inicial de teste: login "admin", senha "admin123" (deverá trocar no primeiro acesso)
-- Hash bcrypt equivalente ao gerado por password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios(nome, login, senha, primeiro_acesso) VALUES
('Administrador', 'admin', '$2b$10$DW0QMOGndxp1Lbi3pFHV4uz.cWIJK6lU6aBEfACCnd10mHaG4iplC', 1);
