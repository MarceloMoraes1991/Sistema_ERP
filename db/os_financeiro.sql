-- ============================================================
--  ITM Technology — Ordens de Serviço + Financeiro
--  Execute no banco: tarefasdiarias
-- ============================================================

USE tarefasdiarias;

-- ------------------------------------------------------------
-- Ordens de Serviço
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `cod`              INT           NOT NULL AUTO_INCREMENT,
  `numero`           VARCHAR(20)   NOT NULL COMMENT 'Ex: OS-2025-0001',
  `cliente_cod`      INT           NOT NULL,
  `titulo`           VARCHAR(200)  NOT NULL,
  `descricao`        TEXT          DEFAULT NULL,
  `tipo_servico`     VARCHAR(80)   DEFAULT NULL COMMENT 'Ex: Suporte, Manutenção, Instalação',
  `prioridade`       ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `status`           ENUM('aberta','em_andamento','aguardando','concluida','cancelada') NOT NULL DEFAULT 'aberta',
  `tecnico`          VARCHAR(100)  DEFAULT NULL,
  `equipamento`      VARCHAR(150)  DEFAULT NULL,
  `problema_relatado`TEXT          DEFAULT NULL,
  `servico_realizado`TEXT          DEFAULT NULL,
  `pecas_utilizadas` TEXT          DEFAULT NULL,
  `valor_servico`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_pecas`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_abertura`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_prevista`    DATE          DEFAULT NULL,
  `data_conclusao`   DATETIME      DEFAULT NULL,
  `observacoes`      TEXT          DEFAULT NULL,
  `usuario_cod`      INT           DEFAULT NULL,
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  UNIQUE KEY `uk_os_numero` (`numero`),
  KEY `fk_os_cliente`  (`cliente_cod`),
  KEY `fk_os_usuario`  (`usuario_cod`),
  CONSTRAINT `fk_os_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes` (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario`   (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- Financeiro — Lançamentos por cliente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `financeiro` (
  `cod`          INT           NOT NULL AUTO_INCREMENT,
  `cliente_cod`  INT           NOT NULL,
  `os_cod`       INT           DEFAULT NULL COMMENT 'OS associada (opcional)',
  `orc_cod`      INT           DEFAULT NULL COMMENT 'Orçamento associado (opcional)',
  `tipo`         ENUM('receita','despesa') NOT NULL DEFAULT 'receita',
  `categoria`    VARCHAR(80)   DEFAULT NULL COMMENT 'Ex: Serviço, Material, Mensalidade',
  `descricao`    VARCHAR(250)  NOT NULL,
  `valor`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_lancamento` DATE       NOT NULL DEFAULT (CURDATE()),
  `data_vencimento` DATE       DEFAULT NULL,
  `data_pagamento`  DATE       DEFAULT NULL,
  `status`       ENUM('pendente','pago','vencido','cancelado') NOT NULL DEFAULT 'pendente',
  `metodo_pag`   VARCHAR(50)   DEFAULT NULL COMMENT 'Ex: Transferência, MB Way, Numerário',
  `referencia`   VARCHAR(100)  DEFAULT NULL,
  `notas`        TEXT          DEFAULT NULL,
  `usuario_cod`  INT           DEFAULT NULL,
  `criado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  KEY `fk_fin_cliente` (`cliente_cod`),
  KEY `fk_fin_os`      (`os_cod`),
  KEY `fk_fin_orc`     (`orc_cod`),
  CONSTRAINT `fk_fin_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes`       (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_fin_os`      FOREIGN KEY (`os_cod`)      REFERENCES `ordens_servico`  (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_fin_orc`     FOREIGN KEY (`orc_cod`)     REFERENCES `orcamentos`      (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
