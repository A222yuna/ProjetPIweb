-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 04 avr. 2026 à 23:21
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `psychologie_app`
--

-- --------------------------------------------------------

--
-- Structure de la table `activite_programme`
--

CREATE TABLE `activite_programme` (
  `idActivite` int(11) NOT NULL,
  `idProgramme` int(11) NOT NULL,
  `jour` int(11) NOT NULL,
  `heureDebut` time NOT NULL,
  `titre` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `dureeMinutes` int(11) DEFAULT NULL,
  `typeActivite` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id_user` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `status` enum('SCHEDULED','CANCELLED','COMPLETED') DEFAULT 'SCHEDULED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `idAvis` int(11) NOT NULL,
  `idProgramme` int(11) NOT NULL,
  `psychologue_id_user` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `dateAvis` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cabinet`
--

CREATE TABLE `cabinet` (
  `id_cabinet` int(11) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `horaires` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0,
  `archive` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id_comment` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `auteur_id_user` int(11) NOT NULL,
  `auteur_role` enum('Patient','Psychologue') NOT NULL,
  `contenu` text NOT NULL,
  `nb_likes` int(11) DEFAULT 0,
  `date` datetime DEFAULT current_timestamp(),
  `parent_comment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id_comment`, `id_post`, `auteur_id_user`, `auteur_role`, `contenu`, `nb_likes`, `date`, `parent_comment_id`) VALUES
(1, 2, 1, 'Patient', 'hhjjk1', 0, '2026-03-02 15:20:42', NULL),
(2, 2, 1, 'Patient', '****', 0, '2026-03-02 15:21:02', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `conversation`
--

CREATE TABLE `conversation` (
  `id_conversation` int(11) NOT NULL,
  `date_creation` date DEFAULT NULL,
  `statut_conversation` varchar(50) DEFAULT NULL,
  `archiver_conversation` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `creneau`
--

CREATE TABLE `creneau` (
  `id` int(11) NOT NULL,
  `disponibilite_id` int(11) NOT NULL,
  `patient_id_user` int(11) NOT NULL,
  `date_creneau` date NOT NULL,
  `heure` time NOT NULL,
  `statut` varchar(20) DEFAULT 'RESERVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `disponibilite`
--

CREATE TABLE `disponibilite` (
  `id` int(11) NOT NULL,
  `cabinet_id` int(11) NOT NULL,
  `jour` tinyint(4) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `duree_consultation` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `id_message` int(11) NOT NULL,
  `contenu_message` text NOT NULL,
  `date_message` datetime DEFAULT current_timestamp(),
  `est_lu` tinyint(1) DEFAULT 0,
  `expediteur_id_user` int(11) NOT NULL,
  `expediteur_role` enum('Patient','Psychologue') NOT NULL,
  `destinataire_id_user` int(11) NOT NULL,
  `destinataire_role` enum('Patient','Psychologue') NOT NULL,
  `id_conversation` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id_post` int(11) NOT NULL,
  `auteur_id_user` int(11) NOT NULL,
  `auteur_role` enum('Patient','Psychologue') NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `categorie` varchar(100) NOT NULL,
  `nb_likes` int(11) DEFAULT 0,
  `date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `post`
--

INSERT INTO `post` (`id_post`, `auteur_id_user`, `auteur_role`, `titre`, `contenu`, `categorie`, `nb_likes`, `date`) VALUES
(2, 1, 'Patient', 'test test test 1', 'test test test test test test test test test test test test test test test test test test', 'Discussion Générale', 0, '2026-03-02 15:14:47'),
(3, 1, 'Patient', 'test test test', 'test test test test test test test test test', 'Discussion Générale', 0, '2026-03-02 15:41:22');

-- --------------------------------------------------------

--
-- Structure de la table `programme_bien_etre`
--

CREATE TABLE `programme_bien_etre` (
  `idProgramme` int(11) NOT NULL,
  `psychologue_id_user` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `objectif` text DEFAULT NULL,
  `duree` int(11) NOT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `niveauDifficulte` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `psychologue_plans`
--

CREATE TABLE `psychologue_plans` (
  `id` int(11) NOT NULL,
  `psychologue_id_user` int(11) NOT NULL,
  `day_of_week` enum('MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY','SUNDAY') NOT NULL,
  `period` enum('DAY','NIGHT') NOT NULL,
  `max_appointments` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `psy_cabinet`
--

CREATE TABLE `psy_cabinet` (
  `id_psy_cabinet` int(11) NOT NULL,
  `psychologue_id_user` int(11) NOT NULL,
  `id_cabinet` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rating`
--

CREATE TABLE `rating` (
  `id` int(11) NOT NULL,
  `patient_id_user` int(11) NOT NULL,
  `cabinet_id` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('Admin','Psychologue','Patient') NOT NULL DEFAULT 'Patient',
  `date_inscription` date DEFAULT curdate(),
  `est_actif` tinyint(1) DEFAULT 1,
  `email_verifie` tinyint(1) DEFAULT 0,
  `derniere_connexion` datetime DEFAULT NULL,
  `statut_validation` enum('en_attente','approuve','rejete') DEFAULT 'approuve'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `nom`, `prenom`, `telephone`, `email`, `mot_de_passe`, `role`, `date_inscription`, `est_actif`, `email_verifie`, `derniere_connexion`, `statut_validation`) VALUES
(1, 'Test', 'Patient', '00000000', 'test@patient.com', 'password123', 'Patient', '2026-03-02', 1, 0, NULL, 'approuve');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activite_programme`
--
ALTER TABLE `activite_programme`
  ADD PRIMARY KEY (`idActivite`),
  ADD KEY `idProgramme` (`idProgramme`);

--
-- Index pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id_user` (`patient_id_user`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`idAvis`),
  ADD KEY `idProgramme` (`idProgramme`),
  ADD KEY `psychologue_id_user` (`psychologue_id_user`);

--
-- Index pour la table `cabinet`
--
ALTER TABLE `cabinet`
  ADD PRIMARY KEY (`id_cabinet`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id_comment`),
  ADD KEY `id_post` (`id_post`),
  ADD KEY `auteur_id_user` (`auteur_id_user`),
  ADD KEY `parent_comment_id` (`parent_comment_id`);

--
-- Index pour la table `conversation`
--
ALTER TABLE `conversation`
  ADD PRIMARY KEY (`id_conversation`);

--
-- Index pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disponibilite_id` (`disponibilite_id`),
  ADD KEY `patient_id_user` (`patient_id_user`);

--
-- Index pour la table `disponibilite`
--
ALTER TABLE `disponibilite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cabinet_id` (`cabinet_id`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `expediteur_id_user` (`expediteur_id_user`),
  ADD KEY `destinataire_id_user` (`destinataire_id_user`),
  ADD KEY `id_conversation` (`id_conversation`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id_post`),
  ADD KEY `auteur_id_user` (`auteur_id_user`);

--
-- Index pour la table `programme_bien_etre`
--
ALTER TABLE `programme_bien_etre`
  ADD PRIMARY KEY (`idProgramme`),
  ADD KEY `psychologue_id_user` (`psychologue_id_user`);

--
-- Index pour la table `psychologue_plans`
--
ALTER TABLE `psychologue_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `psychologue_id_user` (`psychologue_id_user`);

--
-- Index pour la table `psy_cabinet`
--
ALTER TABLE `psy_cabinet`
  ADD PRIMARY KEY (`id_psy_cabinet`),
  ADD UNIQUE KEY `uq_psy_cabinet` (`psychologue_id_user`,`id_cabinet`),
  ADD KEY `id_cabinet` (`id_cabinet`);

--
-- Index pour la table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patient_cabinet` (`patient_id_user`,`cabinet_id`),
  ADD KEY `cabinet_id` (`cabinet_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activite_programme`
--
ALTER TABLE `activite_programme`
  MODIFY `idActivite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `idAvis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `cabinet`
--
ALTER TABLE `cabinet`
  MODIFY `id_cabinet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id_comment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `conversation`
--
ALTER TABLE `conversation`
  MODIFY `id_conversation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `creneau`
--
ALTER TABLE `creneau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `disponibilite`
--
ALTER TABLE `disponibilite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id_post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `programme_bien_etre`
--
ALTER TABLE `programme_bien_etre`
  MODIFY `idProgramme` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `psychologue_plans`
--
ALTER TABLE `psychologue_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `psy_cabinet`
--
ALTER TABLE `psy_cabinet`
  MODIFY `id_psy_cabinet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rating`
--
ALTER TABLE `rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activite_programme`
--
ALTER TABLE `activite_programme`
  ADD CONSTRAINT `activite_programme_ibfk_1` FOREIGN KEY (`idProgramme`) REFERENCES `programme_bien_etre` (`idProgramme`) ON DELETE CASCADE;

--
-- Contraintes pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `psychologue_plans` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`idProgramme`) REFERENCES `programme_bien_etre` (`idProgramme`) ON DELETE CASCADE,
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`psychologue_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`id_post`) REFERENCES `post` (`id_post`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaire_ibfk_2` FOREIGN KEY (`auteur_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaire_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `commentaire` (`id_comment`) ON DELETE SET NULL;

--
-- Contraintes pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD CONSTRAINT `creneau_ibfk_1` FOREIGN KEY (`disponibilite_id`) REFERENCES `disponibilite` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `creneau_ibfk_2` FOREIGN KEY (`patient_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `disponibilite`
--
ALTER TABLE `disponibilite`
  ADD CONSTRAINT `disponibilite_ibfk_1` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinet` (`id_cabinet`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`expediteur_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`destinataire_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_3` FOREIGN KEY (`id_conversation`) REFERENCES `conversation` (`id_conversation`) ON DELETE CASCADE;

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`auteur_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `programme_bien_etre`
--
ALTER TABLE `programme_bien_etre`
  ADD CONSTRAINT `programme_bien_etre_ibfk_1` FOREIGN KEY (`psychologue_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `psychologue_plans`
--
ALTER TABLE `psychologue_plans`
  ADD CONSTRAINT `psychologue_plans_ibfk_1` FOREIGN KEY (`psychologue_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Contraintes pour la table `psy_cabinet`
--
ALTER TABLE `psy_cabinet`
  ADD CONSTRAINT `psy_cabinet_ibfk_1` FOREIGN KEY (`psychologue_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `psy_cabinet_ibfk_2` FOREIGN KEY (`id_cabinet`) REFERENCES `cabinet` (`id_cabinet`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`patient_id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinet` (`id_cabinet`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
