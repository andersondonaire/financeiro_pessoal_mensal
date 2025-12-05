/*
 Navicat Premium Data Transfer

 Source Server         : KingDonaire
 Source Server Type    : MySQL
 Source Server Version : 110408 (11.4.8-MariaDB-log)
 Source Host           : mysql.donaire.com.br:3306
 Source Schema         : donaire

 Target Server Type    : MySQL
 Target Server Version : 110408 (11.4.8-MariaDB-log)
 File Encoding         : 65001

 Date: 05/12/2025 07:02:07
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for categorias
-- ----------------------------
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'fa-tag',
  `cor` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '#6c757d',
  `ordem` int NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ciclos
-- ----------------------------
DROP TABLE IF EXISTS `ciclos`;
CREATE TABLE `ciclos`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `descricao` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_criador_id` int UNSIGNED NULL DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp,
  `fechado` tinyint(1) NOT NULL DEFAULT 0,
  `data_fechamento` timestamp NULL DEFAULT NULL,
  `usuarios_envolvidos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_fechado`(`fechado` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for pagamentos
-- ----------------------------
DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_criador_id` int UNSIGNED NOT NULL,
  `categoria_id` int UNSIGNED NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_total` decimal(10, 2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `parcela_atual` int NULL DEFAULT NULL,
  `total_parcelas` int NULL DEFAULT NULL,
  `cartao_credito` tinyint(1) NOT NULL DEFAULT 0,
  `recorrente` tinyint(1) NOT NULL DEFAULT 0,
  `compartilhado` tinyint(1) NOT NULL DEFAULT 0,
  `usuarios_compartilhados` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `percentuais_divisao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `acerto_ciclo_id` int UNSIGNED NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `usuario_criador_id`(`usuario_criador_id` ASC) USING BTREE,
  INDEX `categoria_id`(`categoria_id` ASC) USING BTREE,
  INDEX `data_vencimento`(`data_vencimento` ASC) USING BTREE,
  CONSTRAINT `fk_pagamentos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_pagamentos_usuario` FOREIGN KEY (`usuario_criador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for pagamentos_ciclo
-- ----------------------------
DROP TABLE IF EXISTS `pagamentos_ciclo`;
CREATE TABLE `pagamentos_ciclo`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ciclo_id` int UNSIGNED NOT NULL,
  `pagamento_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ciclo_id`(`ciclo_id` ASC) USING BTREE,
  INDEX `pagamento_id`(`pagamento_id` ASC) USING BTREE,
  CONSTRAINT `fk_pagciclo_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_pagciclo_pagamento` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for pagamentos_itens
-- ----------------------------
DROP TABLE IF EXISTS `pagamentos_itens`;
CREATE TABLE `pagamentos_itens`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `pagamento_id` int UNSIGNED NOT NULL,
  `data_compra` date NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `valor` decimal(10, 2) NOT NULL,
  `compartilhado` tinyint(1) NULL DEFAULT 0,
  `usuarios_compartilhados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `percentuais_divisao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_pagamento`(`pagamento_id` ASC) USING BTREE,
  CONSTRAINT `pagamentos_itens_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for recebimentos
-- ----------------------------
DROP TABLE IF EXISTS `recebimentos`;
CREATE TABLE `recebimentos`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int UNSIGNED NOT NULL,
  `categoria_id` int UNSIGNED NULL DEFAULT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10, 2) NOT NULL,
  `data_recebimento` date NOT NULL,
  `recorrente` tinyint(1) NOT NULL DEFAULT 0,
  `confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `acerto_ciclo_id` int UNSIGNED NULL DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `usuario_id`(`usuario_id` ASC) USING BTREE,
  INDEX `data_recebimento`(`data_recebimento` ASC) USING BTREE,
  INDEX `idx_categoria_recebimentos`(`categoria_id` ASC) USING BTREE,
  CONSTRAINT `fk_recebimentos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_recebimentos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for usuarios
-- ----------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#007bff',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `email`(`email` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
