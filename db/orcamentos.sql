-- ============================================================
--  ITM Technology — Módulo de Orçamentos
--  Execute no banco: tarefasdiarias
-- ============================================================

USE tarefasdiarias;

-- ------------------------------------------------------------
-- Estados do orçamento
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orcamento_estados` (
  `cod`  INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50) NOT NULL,
  `cor`  VARCHAR(20) NOT NULL DEFAULT 'cinza',
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `orcamento_estados` (`cod`,`nome`,`cor`) VALUES
  (1, 'Rascunho',   'cinza'),
  (2, 'Enviado',    'azul'),
  (3, 'Em análise', 'laranja'),
  (4, 'Aprovado',   'verde'),
  (5, 'Recusado',   'vermelho'),
  (6, 'Cancelado',  'cinza');

-- ------------------------------------------------------------
-- Orçamentos (cabeçalho)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orcamentos` (
  `cod`              INT NOT NULL AUTO_INCREMENT,
  `numero`           VARCHAR(20)  NOT NULL COMMENT 'Ex: ORC-2025-0001',
  `cliente_cod`      INT DEFAULT NULL,
  `cliente_nome`     VARCHAR(150) NOT NULL COMMENT 'Cache do nome caso cliente seja apagado',
  `cliente_email`    VARCHAR(120) DEFAULT NULL,
  `cliente_telef`    VARCHAR(20)  DEFAULT NULL,
  `titulo`           VARCHAR(200) NOT NULL,
  `descricao`        TEXT DEFAULT NULL,
  `estado_cod`       INT NOT NULL DEFAULT 1,
  `validade`         DATE DEFAULT NULL,
  `data_emissao`     DATE NOT NULL DEFAULT (CURDATE()),
  `subtotal`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto_pct`     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `desconto_valor`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `iva_pct`          DECIMAL(5,2) NOT NULL DEFAULT 23.00 COMMENT 'IVA Portugal',
  `iva_valor`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notas`            TEXT DEFAULT NULL,
  `termos`           TEXT DEFAULT NULL,
  `usuario_cod`      INT DEFAULT NULL,
  `criado_em`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  UNIQUE KEY `uk_numero` (`numero`),
  KEY `fk_orc_cliente` (`cliente_cod`),
  KEY `fk_orc_estado`  (`estado_cod`),
  KEY `fk_orc_usuario` (`usuario_cod`),
  CONSTRAINT `fk_orc_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes` (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_orc_estado`  FOREIGN KEY (`estado_cod`)  REFERENCES `orcamento_estados` (`cod`),
  CONSTRAINT `fk_orc_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario` (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- Linhas do orçamento (itens/serviços)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orcamento_linhas` (
  `cod`          INT NOT NULL AUTO_INCREMENT,
  `orcamento_cod`INT NOT NULL,
  `ordem`        INT NOT NULL DEFAULT 1,
  `descricao`    VARCHAR(300) NOT NULL,
  `quantidade`   DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `unidade`      VARCHAR(20)  DEFAULT 'un',
  `preco_unit`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_linha`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`cod`),
  KEY `fk_linha_orc` (`orcamento_cod`),
  CONSTRAINT `fk_linha_orc` FOREIGN KEY (`orcamento_cod`) REFERENCES `orcamentos` (`cod`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
