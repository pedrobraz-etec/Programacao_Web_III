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