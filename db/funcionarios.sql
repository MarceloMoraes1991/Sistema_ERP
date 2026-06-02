-- ============================================================
--  ITM Technology — Módulo de Funcionários
--  Execute no banco: tarefasdiarias
-- ============================================================

USE tarefasdiarias;

CREATE TABLE IF NOT EXISTS `funcionarios` (
  `cod`            INT NOT NULL AUTO_INCREMENT,
  `nome`           VARCHAR(150) NOT NULL,
  `cargo`          VARCHAR(80)  DEFAULT NULL,
  `departamento`   VARCHAR(80)  DEFAULT NULL,
  `email`          VARCHAR(120) DEFAULT NULL,
  `telefone`       VARCHAR(20)  DEFAULT NULL,
  `telemovel`      VARCHAR(20)  DEFAULT NULL,
  `nif`            VARCHAR(15)  DEFAULT NULL,
  `cc`             VARCHAR(20)  DEFAULT NULL COMMENT 'Cartão de Cidadão',
  `data_nascimento`DATE         DEFAULT NULL,
  `data_admissao`  DATE         DEFAULT NULL,
  `data_saida`     DATE         DEFAULT NULL,
  `tipo_contrato`  ENUM('efetivo','termo_certo','termo_incerto','prestacao_servicos','estagio','outro') DEFAULT 'efetivo',
  `salario`        DECIMAL(8,2) DEFAULT NULL,
  `iban`           VARCHAR(30)  DEFAULT NULL,
  `cep`            VARCHAR(10)  DEFAULT NULL,
  `endereco`       VARCHAR(200) DEFAULT NULL,
  `numero`         VARCHAR(10)  DEFAULT NULL,
  `complemento`    VARCHAR(80)  DEFAULT NULL,
  `bairro`         VARCHAR(80)  DEFAULT NULL,
  `cidade`         VARCHAR(80)  DEFAULT NULL,
  `estado`         VARCHAR(50)  DEFAULT NULL,
  `observacoes`    TEXT         DEFAULT NULL,
  `status`         ENUM('ativo','inativo','ferias','baixa') NOT NULL DEFAULT 'ativo',
  `criado_em`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
