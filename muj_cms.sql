-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Počítač: localhost:3306
-- Vytvořeno: Ned 07. pro 2025, 13:15
-- Verze serveru: 9.4.0
-- Verze PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `muj_cms`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `dokumenty`
--

CREATE TABLE `dokumenty` (
  `id` int NOT NULL,
  `smlouva_id` int NOT NULL,
  `typ_dokumentu` varchar(255) NOT NULL,
  `nazev_souboru` varchar(255) NOT NULL,
  `cesta_k_souboru` varchar(500) NOT NULL,
  `poznamka` text,
  `datum_vytvoreni` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struktura tabulky `dokument_typy`
--

CREATE TABLE `dokument_typy` (
  `id` int NOT NULL,
  `typ` varchar(255) NOT NULL,
  `pocet_pouziti` int DEFAULT '1',
  `datum_posledniho_pouziti` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Struktura tabulky `klienti`
--

CREATE TABLE `klienti` (
  `id` int NOT NULL,
  `jmeno` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `telefon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `rc_ico` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL COMMENT 'Rodné číslo nebo IČO',
  `ulice` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `mesto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `psc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `korespondencni_ulice` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `korespondencni_mesto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `korespondencni_psc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `pojistovny`
--

CREATE TABLE `pojistovny` (
  `id` int NOT NULL,
  `nazev` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `pojistovny`
--

INSERT INTO `pojistovny` (`id`, `nazev`, `datum_vytvoreni`) VALUES
(1, 'Allianz', '2025-12-06 13:21:56'),
(2, 'ČPP', '2025-12-06 13:21:56'),
(3, 'Kooperativa', '2025-12-06 13:21:56'),
(4, 'Maxima', '2025-12-06 13:21:56'),
(5, 'Slavia', '2025-12-06 13:21:56'),
(6, 'Uniqa', '2025-12-06 13:21:56'),
(7, 'RaiffeisenBank', '2025-12-06 13:21:56'),
(8, 'Conseq', '2025-12-06 13:21:56'),
(9, 'KB', '2025-12-06 13:21:56'),
(10, 'PVZP', '2025-12-06 13:21:56'),
(11, 'AuRenta', '2025-12-06 13:21:56'),
(12, 'Comfort Commodity', '2025-12-06 13:21:56'),
(13, 'AXA', '2025-12-06 13:21:56'),
(14, 'Investika', '2025-12-06 13:21:56'),
(15, 'Investona', '2025-12-06 13:21:56');

-- --------------------------------------------------------

--
-- Struktura tabulky `predavaci_dokumenty`
--

CREATE TABLE `predavaci_dokumenty` (
  `id` int NOT NULL,
  `cislo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `cesta_k_souboru` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `produkty`
--

CREATE TABLE `produkty` (
  `id` int NOT NULL,
  `nazev` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `popis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Vypisuji data pro tabulku `produkty`
--

INSERT INTO `produkty` (`id`, `nazev`, `popis`, `datum_vytvoreni`) VALUES
(1, 'Autopojištění', NULL, '2025-12-06 12:57:17'),
(2, 'Cestovní pojištění', NULL, '2025-12-06 12:57:17'),
(3, 'Cizinci', NULL, '2025-12-06 12:57:17'),
(4, 'Investice', NULL, '2025-12-06 12:57:17'),
(5, 'Penzijní připojištění', NULL, '2025-12-06 12:57:17'),
(6, 'Odpovědnost v běžném životě', NULL, '2025-12-06 12:57:17'),
(7, 'Odpovědnost vůči zaměstnavateli', NULL, '2025-12-06 12:57:17'),
(8, 'Pojištění nemovitosti', NULL, '2025-12-06 12:57:17'),
(9, 'Veterinární služba', NULL, '2025-12-06 12:57:17'),
(10, 'Stavební spoření', NULL, '2025-12-06 12:57:17'),
(11, 'Životní pojištění', NULL, '2025-12-06 12:57:17'),
(12, 'Pojištění bytového domu', NULL, '2025-12-06 12:57:17');

-- --------------------------------------------------------

--
-- Struktura tabulky `provize`
--

CREATE TABLE `provize` (
  `id` int NOT NULL,
  `smlouva_id` int NOT NULL,
  `castka` decimal(10,2) NOT NULL COMMENT 'Částka provize v Kč',
  `stornovana` tinyint(1) DEFAULT '0',
  `storno_rezerva` decimal(10,2) DEFAULT '0.00',
  `predavaci_dokument_cislo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `cislo_vypisu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `stupen_vyplaceni` tinyint NOT NULL DEFAULT '1',
  `datum_vyplaty` date DEFAULT NULL COMMENT 'Skutečné datum zaplacení (NULL pokud není zaplaceno)',
  `poznamka` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `smlouvy`
--

CREATE TABLE `smlouvy` (
  `id` int NOT NULL,
  `cislo_smlouvy` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `klient_id` int NOT NULL,
  `produkt_id` int NOT NULL,
  `pojistovna_id` int NOT NULL,
  `datum_sjednani` date NOT NULL,
  `datum_platnosti` date DEFAULT NULL,
  `cesta_k_souboru` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `zaznam_zeteo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `poznamka` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `podminky_produktu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci COMMENT 'JSON s dynamickými poli (např. limit plnění)',
  `predavaci_dokument_id` int DEFAULT NULL,
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_czech_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_czech_ci NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `datum_vytvoreni` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `posledni_prihlaseni` timestamp NULL DEFAULT NULL,
  `aktivni` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `dokumenty`
--
ALTER TABLE `dokumenty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `smlouva_id` (`smlouva_id`);

--
-- Indexy pro tabulku `dokument_typy`
--
ALTER TABLE `dokument_typy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `typ` (`typ`);

--
-- Indexy pro tabulku `klienti`
--
ALTER TABLE `klienti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `rc_ico` (`rc_ico`);

--
-- Indexy pro tabulku `pojistovny`
--
ALTER TABLE `pojistovny`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazev` (`nazev`);

--
-- Indexy pro tabulku `predavaci_dokumenty`
--
ALTER TABLE `predavaci_dokumenty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cislo` (`cislo`);

--
-- Indexy pro tabulku `produkty`
--
ALTER TABLE `produkty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazev` (`nazev`);

--
-- Indexy pro tabulku `provize`
--
ALTER TABLE `provize`
  ADD PRIMARY KEY (`id`),
  ADD KEY `smlouva_id` (`smlouva_id`);

--
-- Indexy pro tabulku `smlouvy`
--
ALTER TABLE `smlouvy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cislo_smlouvy` (`cislo_smlouvy`),
  ADD KEY `klient_id` (`klient_id`),
  ADD KEY `produkt_id` (`produkt_id`),
  ADD KEY `pojistovna_id` (`pojistovna_id`),
  ADD KEY `smlouvy_ibfk_4` (`predavaci_dokument_id`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `dokumenty`
--
ALTER TABLE `dokumenty`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `dokument_typy`
--
ALTER TABLE `dokument_typy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `klienti`
--
ALTER TABLE `klienti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `pojistovny`
--
ALTER TABLE `pojistovny`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pro tabulku `predavaci_dokumenty`
--
ALTER TABLE `predavaci_dokumenty`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `produkty`
--
ALTER TABLE `produkty`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pro tabulku `provize`
--
ALTER TABLE `provize`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `smlouvy`
--
ALTER TABLE `smlouvy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `dokumenty`
--
ALTER TABLE `dokumenty`
  ADD CONSTRAINT `dokumenty_ibfk_1` FOREIGN KEY (`smlouva_id`) REFERENCES `smlouvy` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `provize`
--
ALTER TABLE `provize`
  ADD CONSTRAINT `provize_ibfk_1` FOREIGN KEY (`smlouva_id`) REFERENCES `smlouvy` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `smlouvy`
--
ALTER TABLE `smlouvy`
  ADD CONSTRAINT `smlouvy_ibfk_1` FOREIGN KEY (`klient_id`) REFERENCES `klienti` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `smlouvy_ibfk_2` FOREIGN KEY (`produkt_id`) REFERENCES `produkty` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `smlouvy_ibfk_3` FOREIGN KEY (`pojistovna_id`) REFERENCES `pojistovny` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `smlouvy_ibfk_4` FOREIGN KEY (`predavaci_dokument_id`) REFERENCES `predavaci_dokumenty` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
