-- ============================================================
--  ITM Technology — Módulo de Backup
--  Execute no banco: tarefasdiarias
-- ============================================================

USE tarefasdiarias;

-- Configurações dos destinos de backup
CREATE TABLE IF NOT EXISTS `backup_configs` (
  `cod`        INT         NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(80) NOT NULL,
  `tipo`       ENUM('ftp','google_drive','local','mega','sftp','s3') NOT NULL,
  `ativo`      TINYINT(1)  NOT NULL DEFAULT 1,
  `config_json`TEXT        NOT NULL COMMENT 'JSON com as configurações do destino',
  `criado_em`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Histórico de backups realizados
CREATE TABLE IF NOT EXISTS `backup_historico` (
  `cod`         INT          NOT NULL AUTO_INCREMENT,
  `config_cod`  INT          DEFAULT NULL,
  `destino`     VARCHAR(80)  NOT NULL,
  `tipo`        VARCHAR(30)  NOT NULL,
  `arquivo`     VARCHAR(255) NOT NULL,
  `tamanho`     BIGINT       DEFAULT NULL COMMENT 'Tamanho em bytes',
  `status`      ENUM('sucesso','erro','a_processar') NOT NULL DEFAULT 'a_processar',
  `mensagem`    TEXT         DEFAULT NULL,
  `usuario_cod` INT          DEFAULT NULL,
  `iniciado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em`DATETIME     DEFAULT NULL,
  PRIMARY KEY (`cod`),
  KEY `fk_bk_config`  (`config_cod`),
  KEY `fk_bk_usuario` (`usuario_cod`),
  CONSTRAINT `fk_bk_config`  FOREIGN KEY (`config_cod`)  REFERENCES `backup_configs` (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_bk_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario`         (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
