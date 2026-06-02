
-- ============================================================
-- 1. PERFIS DE UTILIZADOR
-- ============================================================
CREATE TABLE IF NOT EXISTS `perfil_usuario` (
  `cod`  INT         NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `perfil_usuario` (`cod`,`nome`) VALUES
  (1, 'Administrador'),
  (2, 'Utilizador Padrão');

-- ============================================================
-- 2. UTILIZADORES
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuario` (
  `cod`        INT          NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `senha`      VARCHAR(255) NOT NULL,
  `perfil_cod` INT          NOT NULL DEFAULT 2,
  PRIMARY KEY (`cod`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `fk_usuario_perfil` (`perfil_cod`),
  CONSTRAINT `fk_usuario_perfil`
    FOREIGN KEY (`perfil_cod`) REFERENCES `perfil_usuario` (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 3. CATEGORIAS DE TAREFA
-- ============================================================
CREATE TABLE IF NOT EXISTS `categoria_tarefa` (
  `cod`  INT         NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `categoria_tarefa` (`cod`,`nome`) VALUES
  (1,'Trabalho'),(2,'Suporte'),(3,'Manutenção'),
  (4,'Compra'),(5,'Reunião'),(6,'Outros');

-- ============================================================
-- 4. TAREFAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `tarefas` (
  `cod`           INT          NOT NULL AUTO_INCREMENT,
  `titulo`        VARCHAR(100) NOT NULL,
  `data`          DATE         NOT NULL,
  `hora`          TIME         NOT NULL,
  `descricao`     VARCHAR(500) DEFAULT NULL,
  `usuario_cod`   INT          NOT NULL,
  `categoria_cod` INT          NOT NULL,
  PRIMARY KEY (`cod`),
  KEY `fk_tarefas_usuario`   (`usuario_cod`),
  KEY `fk_tarefas_categoria` (`categoria_cod`),
  CONSTRAINT `fk_tarefas_usuario`   FOREIGN KEY (`usuario_cod`)   REFERENCES `usuario`          (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_tarefas_categoria` FOREIGN KEY (`categoria_cod`) REFERENCES `categoria_tarefa` (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 5. ATIVIDADES (CHAMADOS)
-- ============================================================
CREATE TABLE IF NOT EXISTS `atividades` (
  `cod`             INT          NOT NULL AUTO_INCREMENT,
  `titulo`          VARCHAR(150) NOT NULL,
  `aberto_por`      VARCHAR(100) DEFAULT NULL,
  `observacao`      TEXT         DEFAULT NULL,
  `fomentar`        VARCHAR(100) DEFAULT NULL,
  `status`          ENUM('aberta','em_andamento','concluida','arquivada') NOT NULL DEFAULT 'aberta',
  `data_criacao`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_fechamento` DATETIME     DEFAULT NULL,
  `usuario_cod`     INT          DEFAULT NULL,
  PRIMARY KEY (`cod`),
  KEY `fk_atividade_usuario` (`usuario_cod`),
  CONSTRAINT `fk_atividade_usuario`
    FOREIGN KEY (`usuario_cod`) REFERENCES `usuario` (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 6. CONTROLO DE EQUIPAMENTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `controle_equipamentos` (
  `cod`              INT          NOT NULL AUTO_INCREMENT,
  `nome_funcionario` VARCHAR(100) DEFAULT NULL,
  `cpf`              VARCHAR(20)  DEFAULT NULL,
  `equipamento`      VARCHAR(100) NOT NULL,
  `modelo`           VARCHAR(100) DEFAULT NULL,
  `sn`               VARCHAR(100) DEFAULT NULL,
  `fabricante`       VARCHAR(80)  DEFAULT NULL,
  `mtm`              VARCHAR(80)  DEFAULT NULL,
  `mo`               VARCHAR(80)  DEFAULT NULL,
  `descricao`        TEXT         DEFAULT NULL,
  `anexo`            VARCHAR(255) DEFAULT NULL,
  `data_cadastro`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 7. CHIPS / OPERADORAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `chips` (
  `cod`       INT          NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(100) NOT NULL,
  `numero`    VARCHAR(30)  DEFAULT NULL,
  `operadora` VARCHAR(60)  DEFAULT NULL,
  `qrcode`    TEXT         DEFAULT NULL,
  `status`    ENUM('ativo','arquivado') NOT NULL DEFAULT 'ativo',
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 8. CONTRATOS / DOCUMENTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `contratos` (
  `cod`       INT          NOT NULL AUTO_INCREMENT,
  `titulo`    VARCHAR(150) NOT NULL,
  `arquivo`   VARCHAR(255) DEFAULT NULL,
  `descricao` TEXT         DEFAULT NULL,
  `criado_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 9. CLIENTES
-- ============================================================
CREATE TABLE IF NOT EXISTS `clientes` (
  `cod`             INT          NOT NULL AUTO_INCREMENT,
  `nome_completo`   VARCHAR(150) NOT NULL,
  `tipo_pessoa`     ENUM('fisica','juridica') NOT NULL DEFAULT 'fisica',
  `cpf_cnpj`        VARCHAR(20)  DEFAULT NULL,
  `rg`              VARCHAR(20)  DEFAULT NULL,
  `data_nascimento` DATE         DEFAULT NULL,
  `sexo`            ENUM('masculino','feminino','outro') DEFAULT NULL,
  `nacionalidade`   VARCHAR(60)  DEFAULT NULL,
  `email`           VARCHAR(120) DEFAULT NULL,
  `telefone`        VARCHAR(20)  DEFAULT NULL,
  `celular`         VARCHAR(20)  DEFAULT NULL,
  `whatsapp`        VARCHAR(20)  DEFAULT NULL,
  `cep`             VARCHAR(10)  DEFAULT NULL,
  `endereco`        VARCHAR(200) DEFAULT NULL,
  `numero`          VARCHAR(10)  DEFAULT NULL,
  `complemento`     VARCHAR(80)  DEFAULT NULL,
  `bairro`          VARCHAR(80)  DEFAULT NULL,
  `cidade`          VARCHAR(80)  DEFAULT NULL,
  `estado`          VARCHAR(50)  DEFAULT NULL,
  `referencia`      VARCHAR(200) DEFAULT NULL,
  `observacoes`     TEXT         DEFAULT NULL,
  `status`          ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `criado_em`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 10. FUNCIONÁRIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `funcionarios` (
  `cod`             INT           NOT NULL AUTO_INCREMENT,
  `nome`            VARCHAR(150)  NOT NULL,
  `cargo`           VARCHAR(80)   DEFAULT NULL,
  `departamento`    VARCHAR(80)   DEFAULT NULL,
  `email`           VARCHAR(120)  DEFAULT NULL,
  `telefone`        VARCHAR(20)   DEFAULT NULL,
  `telemovel`       VARCHAR(20)   DEFAULT NULL,
  `nif`             VARCHAR(15)   DEFAULT NULL,
  `cc`              VARCHAR(20)   DEFAULT NULL,
  `data_nascimento` DATE          DEFAULT NULL,
  `data_admissao`   DATE          DEFAULT NULL,
  `data_saida`      DATE          DEFAULT NULL,
  `tipo_contrato`   ENUM('efetivo','termo_certo','termo_incerto','prestacao_servicos','estagio','outro') DEFAULT 'efetivo',
  `salario`         DECIMAL(8,2)  DEFAULT NULL,
  `iban`            VARCHAR(30)   DEFAULT NULL,
  `cep`             VARCHAR(10)   DEFAULT NULL,
  `endereco`        VARCHAR(200)  DEFAULT NULL,
  `numero`          VARCHAR(10)   DEFAULT NULL,
  `complemento`     VARCHAR(80)   DEFAULT NULL,
  `bairro`          VARCHAR(80)   DEFAULT NULL,
  `cidade`          VARCHAR(80)   DEFAULT NULL,
  `estado`          VARCHAR(50)   DEFAULT NULL,
  `observacoes`     TEXT          DEFAULT NULL,
  `status`          ENUM('ativo','inativo','ferias','baixa') NOT NULL DEFAULT 'ativo',
  `criado_em`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 11. ESTOQUE — CATEGORIAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `estoque_categorias` (
  `cod`  INT         NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(80) NOT NULL,
  `tipo` ENUM('material','equipamento','ambos') NOT NULL DEFAULT 'ambos',
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `estoque_categorias` (`cod`,`nome`,`tipo`) VALUES
  (1, 'Cabos e Adaptadores',   'material'),
  (2, 'Memórias RAM',          'material'),
  (3, 'HDs e SSDs',            'material'),
  (4, 'Fontes de Alimentação', 'material'),
  (5, 'Portáteis / Notebooks', 'equipamento'),
  (6, 'Desktops',              'equipamento'),
  (7, 'Routers / Switches',    'equipamento'),
  (8, 'Periféricos',           'equipamento'),
  (9, 'Chips / SIMs',          'material'),
  (10,'Outros',                'ambos');

-- ============================================================
-- 12. ESTOQUE — ITENS
-- ============================================================
CREATE TABLE IF NOT EXISTS `estoque` (
  `cod`           INT           NOT NULL AUTO_INCREMENT,
  `tipo`          ENUM('material','equipamento') NOT NULL,
  `categoria_cod` INT           NOT NULL,
  `nome`          VARCHAR(120)  NOT NULL,
  `marca`         VARCHAR(80)   DEFAULT NULL,
  `modelo`        VARCHAR(120)  DEFAULT NULL,
  `numero_serie`  VARCHAR(100)  DEFAULT NULL,
  `patrimonio`    VARCHAR(60)   DEFAULT NULL,
  `quantidade`    INT           NOT NULL DEFAULT 0,
  `qtd_minima`    INT           NOT NULL DEFAULT 1,
  `localizacao`   VARCHAR(120)  DEFAULT NULL,
  `responsavel`   VARCHAR(120)  DEFAULT NULL,
  `status`        ENUM('disponivel','em_uso','manutencao','baixado') NOT NULL DEFAULT 'disponivel',
  `observacoes`   TEXT          DEFAULT NULL,
  `criado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  KEY `fk_estoque_cat` (`categoria_cod`),
  CONSTRAINT `fk_estoque_cat`
    FOREIGN KEY (`categoria_cod`) REFERENCES `estoque_categorias` (`cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 13. ESTOQUE — MOVIMENTAÇÕES
-- ============================================================
CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
  `cod`         INT          NOT NULL AUTO_INCREMENT,
  `estoque_cod` INT          NOT NULL,
  `tipo_mov`    ENUM('entrada','saida','ajuste') NOT NULL,
  `quantidade`  INT          NOT NULL,
  `motivo`      VARCHAR(255) DEFAULT NULL,
  `responsavel` VARCHAR(120) DEFAULT NULL,
  `usuario_cod` INT          DEFAULT NULL,
  `data_mov`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  KEY `fk_mov_est` (`estoque_cod`),
  KEY `fk_mov_usr` (`usuario_cod`),
  CONSTRAINT `fk_mov_est` FOREIGN KEY (`estoque_cod`) REFERENCES `estoque`  (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usr` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario` (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 14. ESTADOS DO ORÇAMENTO
-- ============================================================
CREATE TABLE IF NOT EXISTS `orcamento_estados` (
  `cod`  INT         NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50) NOT NULL,
  `cor`  VARCHAR(20) NOT NULL DEFAULT 'cinza',
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `orcamento_estados` (`cod`,`nome`,`cor`) VALUES
  (1,'Rascunho',   'cinza'),
  (2,'Enviado',    'azul'),
  (3,'Em análise', 'laranja'),
  (4,'Aprovado',   'verde'),
  (5,'Recusado',   'vermelho'),
  (6,'Cancelado',  'cinza');

-- ============================================================
-- 15. ORÇAMENTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `orcamentos` (
  `cod`              INT           NOT NULL AUTO_INCREMENT,
  `numero`           VARCHAR(20)   NOT NULL,
  `cliente_cod`      INT           DEFAULT NULL,
  `cliente_nome`     VARCHAR(150)  NOT NULL,
  `cliente_email`    VARCHAR(120)  DEFAULT NULL,
  `cliente_telef`    VARCHAR(20)   DEFAULT NULL,
  `titulo`           VARCHAR(200)  NOT NULL,
  `descricao`        TEXT          DEFAULT NULL,
  `estado_cod`       INT           NOT NULL DEFAULT 1,
  `validade`         DATE          DEFAULT NULL,
  `data_emissao`     DATE          NOT NULL DEFAULT (CURDATE()),
  `subtotal`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto_pct`     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `desconto_valor`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `iva_pct`          DECIMAL(5,2)  NOT NULL DEFAULT 23.00,
  `iva_valor`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notas`            TEXT          DEFAULT NULL,
  `termos`           TEXT          DEFAULT NULL,
  `usuario_cod`      INT           DEFAULT NULL,
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  UNIQUE KEY `uk_orc_numero` (`numero`),
  KEY `fk_orc_cliente` (`cliente_cod`),
  KEY `fk_orc_estado`  (`estado_cod`),
  KEY `fk_orc_usuario` (`usuario_cod`),
  CONSTRAINT `fk_orc_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes`          (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_orc_estado`  FOREIGN KEY (`estado_cod`)  REFERENCES `orcamento_estados` (`cod`),
  CONSTRAINT `fk_orc_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario`            (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 16. LINHAS DO ORÇAMENTO
-- ============================================================
CREATE TABLE IF NOT EXISTS `orcamento_linhas` (
  `cod`           INT           NOT NULL AUTO_INCREMENT,
  `orcamento_cod` INT           NOT NULL,
  `ordem`         INT           NOT NULL DEFAULT 1,
  `descricao`     VARCHAR(300)  NOT NULL,
  `quantidade`    DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `unidade`       VARCHAR(20)   DEFAULT 'un',
  `preco_unit`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto_pct`  DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `total_linha`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`cod`),
  KEY `fk_linha_orc` (`orcamento_cod`),
  CONSTRAINT `fk_linha_orc`
    FOREIGN KEY (`orcamento_cod`) REFERENCES `orcamentos` (`cod`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 17. ORDENS DE SERVIÇO
-- ============================================================
CREATE TABLE IF NOT EXISTS `ordens_servico` (
  `cod`               INT           NOT NULL AUTO_INCREMENT,
  `numero`            VARCHAR(20)   NOT NULL,
  `cliente_cod`       INT           NOT NULL,
  `titulo`            VARCHAR(200)  NOT NULL,
  `descricao`         TEXT          DEFAULT NULL,
  `tipo_servico`      VARCHAR(80)   DEFAULT NULL,
  `prioridade`        ENUM('baixa','normal','alta','urgente') NOT NULL DEFAULT 'normal',
  `status`            ENUM('aberta','em_andamento','aguardando','concluida','cancelada') NOT NULL DEFAULT 'aberta',
  `tecnico`           VARCHAR(100)  DEFAULT NULL,
  `equipamento`       VARCHAR(150)  DEFAULT NULL,
  `problema_relatado` TEXT          DEFAULT NULL,
  `servico_realizado` TEXT          DEFAULT NULL,
  `pecas_utilizadas`  TEXT          DEFAULT NULL,
  `valor_servico`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_pecas`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `valor_total`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_abertura`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_prevista`     DATE          DEFAULT NULL,
  `data_conclusao`    DATETIME      DEFAULT NULL,
  `observacoes`       TEXT          DEFAULT NULL,
  `usuario_cod`       INT           DEFAULT NULL,
  `criado_em`         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  UNIQUE KEY `uk_os_numero` (`numero`),
  KEY `fk_os_cliente`  (`cliente_cod`),
  KEY `fk_os_usuario`  (`usuario_cod`),
  CONSTRAINT `fk_os_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes` (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_os_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario`  (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 18. FINANCEIRO
-- ============================================================
CREATE TABLE IF NOT EXISTS `financeiro` (
  `cod`              INT           NOT NULL AUTO_INCREMENT,
  `cliente_cod`      INT           NOT NULL,
  `os_cod`           INT           DEFAULT NULL,
  `orc_cod`          INT           DEFAULT NULL,
  `tipo`             ENUM('receita','despesa') NOT NULL DEFAULT 'receita',
  `categoria`        VARCHAR(80)   DEFAULT NULL,
  `descricao`        VARCHAR(250)  NOT NULL,
  `valor`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `data_lancamento`  DATE          NOT NULL DEFAULT (CURDATE()),
  `data_vencimento`  DATE          DEFAULT NULL,
  `data_pagamento`   DATE          DEFAULT NULL,
  `status`           ENUM('pendente','pago','vencido','cancelado') NOT NULL DEFAULT 'pendente',
  `metodo_pag`       VARCHAR(50)   DEFAULT NULL,
  `referencia`       VARCHAR(100)  DEFAULT NULL,
  `notas`            TEXT          DEFAULT NULL,
  `usuario_cod`      INT           DEFAULT NULL,
  `criado_em`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`),
  KEY `fk_fin_cliente` (`cliente_cod`),
  KEY `fk_fin_os`      (`os_cod`),
  KEY `fk_fin_orc`     (`orc_cod`),
  CONSTRAINT `fk_fin_cliente` FOREIGN KEY (`cliente_cod`) REFERENCES `clientes`      (`cod`) ON DELETE CASCADE,
  CONSTRAINT `fk_fin_os`      FOREIGN KEY (`os_cod`)      REFERENCES `ordens_servico` (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_fin_orc`     FOREIGN KEY (`orc_cod`)     REFERENCES `orcamentos`    (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 19. BACKUP — CONFIGURAÇÕES
-- ============================================================
CREATE TABLE IF NOT EXISTS `backup_configs` (
  `cod`         INT         NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(80) NOT NULL,
  `tipo`        ENUM('ftp','sftp','google_drive','mega','s3','local') NOT NULL,
  `ativo`       TINYINT(1)  NOT NULL DEFAULT 1,
  `config_json` TEXT        NOT NULL,
  `criado_em`   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- 20. BACKUP — HISTÓRICO
-- ============================================================
CREATE TABLE IF NOT EXISTS `backup_historico` (
  `cod`          INT          NOT NULL AUTO_INCREMENT,
  `config_cod`   INT          DEFAULT NULL,
  `destino`      VARCHAR(80)  NOT NULL,
  `tipo`         VARCHAR(30)  NOT NULL,
  `arquivo`      VARCHAR(255) NOT NULL,
  `tamanho`      BIGINT       DEFAULT NULL,
  `status`       ENUM('sucesso','erro','a_processar') NOT NULL DEFAULT 'a_processar',
  `mensagem`     TEXT         DEFAULT NULL,
  `usuario_cod`  INT          DEFAULT NULL,
  `iniciado_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` DATETIME     DEFAULT NULL,
  PRIMARY KEY (`cod`),
  KEY `fk_bk_config`  (`config_cod`),
  KEY `fk_bk_usuario` (`usuario_cod`),
  CONSTRAINT `fk_bk_config`  FOREIGN KEY (`config_cod`)  REFERENCES `backup_configs` (`cod`) ON DELETE SET NULL,
  CONSTRAINT `fk_bk_usuario` FOREIGN KEY (`usuario_cod`) REFERENCES `usuario`         (`cod`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================
-- UTILIZADOR MASTER
-- ============================================================
-- E-mail : moraes.marcelo.tic@gmail.com
-- Senha  : Admin@2025  (MD5)
-- Perfil : Administrador
-- ⚠️ Altere a palavra-passe após o primeiro acesso!
-- ============================================================
INSERT IGNORE INTO `perfil_usuario` (`cod`,`nome`) VALUES (1,'Administrador'),(2,'Utilizador Padrão');

DELETE FROM `usuario` WHERE `email` = 'admin@admin.com';
INSERT INTO `usuario` (`nome`, `email`, `senha`, `perfil_cod`)
VALUES (
  'Administrador',
  'admin@admin.com',
  '82f9fa29d86dae71395f7fc9ef23fe5f',
  1
);

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
