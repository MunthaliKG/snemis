-- MySQL Script generated by MySQL Workbench
-- 08/01/15 18:25:56
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema snlis
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema snlis
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `snlis` DEFAULT CHARACTER SET latin1 ;
USE `snlis` ;

-- -----------------------------------------------------
-- Table `snlis`.`disability_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`disability_category` (
  `iddisability_category` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(50) NULL DEFAULT NULL,
  `category_description` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`iddisability_category`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`disability`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`disability` (
  `iddisability` INT(11) NOT NULL AUTO_INCREMENT,
  `iddisability_category` INT(11) NULL DEFAULT NULL,
  `teacher_speciality_required` SET('VI','HI','LD') NOT NULL,
  `disability_name` VARCHAR(50) NOT NULL,
  `disability_description` VARCHAR(500) NOT NULL,
  `general_category` ENUM('disability','special need') NULL DEFAULT NULL COMMENT 'This attribute will differentiate between a learner with disability and one who just has a special need e.g orphans, refugees',
  PRIMARY KEY (`iddisability`),
  INDEX `fk_disability_disability_category1_idx` (`iddisability_category` ASC),
  CONSTRAINT `fk_disability_disability_category1`
    FOREIGN KEY (`iddisability_category`)
    REFERENCES `snlis`.`disability_category` (`iddisability_category`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table keeps track of each disability';


-- -----------------------------------------------------
-- Table `snlis`.`level`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`level` (
  `idlevel` INT(11) NOT NULL AUTO_INCREMENT,
  `level_name` VARCHAR(12) NOT NULL,
  PRIMARY KEY (`idlevel`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table will contain all the different levels disabilities could have e.g. low, high, moderate, severe, total, partial';


-- -----------------------------------------------------
-- Table `snlis`.`disability_has_level`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`disability_has_level` (
  `iddisability` INT(11) NOT NULL,
  `idlevel` INT(11) NOT NULL,
  PRIMARY KEY (`iddisability`, `idlevel`),
  INDEX `fk_disability_has_level_disability1_idx` (`iddisability` ASC),
  INDEX `fk_disability_has_level_level1_idx` (`idlevel` ASC),
  CONSTRAINT `fk_disability_has_level_disability1`
    FOREIGN KEY (`iddisability`)
    REFERENCES `snlis`.`disability` (`iddisability`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_disability_has_level_level1`
    FOREIGN KEY (`idlevel`)
    REFERENCES `snlis`.`level` (`idlevel`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table matches each disability with the its corresponding levels. Visual impairment for example, will have two entries in this table, one matched with \'low\' and another matched with \'total\'';


-- -----------------------------------------------------
-- Table `snlis`.`need`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`need` (
  `idneed` INT(11) NOT NULL,
  `needname` VARCHAR(45) NOT NULL,
  `type` ENUM('learning','mobility','facility') NOT NULL COMMENT 'This attribute defines the type of need i.e. learning need, mobility need, facility need',
  `available_in_rc` ENUM('yes','no') NULL DEFAULT NULL,
  `quantity` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`idneed`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table includes needs (specific to a disability)  as well as facilities ( Specific to a school).\n';


-- -----------------------------------------------------
-- Table `snlis`.`disability_has_need`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`disability_has_need` (
  `idneed` INT(11) NOT NULL,
  `iddisability` INT(11) NOT NULL,
  `disability_has_needcol` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`idneed`, `iddisability`),
  INDEX `fk_need_has_disability_disability1_idx` (`iddisability` ASC),
  INDEX `fk_need_has_disability_need1_idx` (`idneed` ASC),
  CONSTRAINT `fk_need_has_disability_disability1`
    FOREIGN KEY (`iddisability`)
    REFERENCES `snlis`.`disability` (`iddisability`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_need_has_disability_need1`
    FOREIGN KEY (`idneed`)
    REFERENCES `snlis`.`need` (`idneed`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`disability_needs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`disability_needs` (
  `iddisability_needs` INT(11) NOT NULL,
  PRIMARY KEY (`iddisability_needs`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`district`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`district` (
  `iddistrict` INT(11) NOT NULL AUTO_INCREMENT,
  `district_name` VARCHAR(20) NULL DEFAULT NULL,
  PRIMARY KEY (`iddistrict`))
ENGINE = InnoDB
AUTO_INCREMENT = 35
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`guardian`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`guardian` (
  `idguardian` INT(11) NOT NULL AUTO_INCREMENT,
  `gfirst_name` VARCHAR(25) NOT NULL,
  `glast_name` VARCHAR(25) NOT NULL,
  `gdob` DATE NOT NULL,
  `gsex` ENUM('M','F') NOT NULL,
  `gaddress` VARCHAR(100) NOT NULL,
  `occupation` VARCHAR(20) NULL DEFAULT NULL,
  `income_level` ENUM('low','medium','high') NULL DEFAULT NULL COMMENT 'For this field, we are looking for the equivalent in MWK of whatever their earnings might be e.g bags of maize.',
  `district` VARCHAR(11) NOT NULL,
  PRIMARY KEY (`idguardian`))
ENGINE = InnoDB
AUTO_INCREMENT = 11
DEFAULT CHARACTER SET = utf8
COMMENT = 'Collects information about the learner\'s guardian(s) and the support they are able to provide to the learner, and their income. We still need to come up with a good indicator ';


-- -----------------------------------------------------
-- Table `snlis`.`lwd`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`lwd` (
  `idlwd` INT(11) NOT NULL,
  `distance_to_school` INT(11) NULL DEFAULT NULL,
  `first_name` VARCHAR(25) NOT NULL,
  `last_name` VARCHAR(25) NOT NULL,
  `initials` VARCHAR(8) NULL DEFAULT NULL,
  `home_address` VARCHAR(100) NOT NULL,
  `sex` ENUM('M','F') NOT NULL,
  `dob` DATE NOT NULL,
  `idguardian` INT(11) NOT NULL,
  `guardian_relationship` ENUM('parent','sibling','other') NOT NULL,
  PRIMARY KEY (`idlwd`),
  INDEX `fk_lwd_guardian1_idx` (`idguardian` ASC),
  CONSTRAINT `fk_lwd_guardian1`
    FOREIGN KEY (`idguardian`)
    REFERENCES `snlis`.`guardian` (`idguardian`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'Represents the Learner with Disability (LWD), who is the main Entity for this Database';


-- -----------------------------------------------------
-- Table `snlis`.`zone`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`zone` (
  `idzone` INT(11) NOT NULL,
  `district_iddistrict` INT(11) NOT NULL,
  `zone_name` VARCHAR(20) NULL DEFAULT NULL,
  PRIMARY KEY (`idzone`),
  INDEX `fk_zone_district1_idx` (`district_iddistrict` ASC),
  CONSTRAINT `fk_zone_district1`
    FOREIGN KEY (`district_iddistrict`)
    REFERENCES `snlis`.`district` (`iddistrict`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`school`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`school` (
  `emiscode` INT(11) NOT NULL,
  `idzone` INT(11) NOT NULL,
  `school_name` VARCHAR(50) NOT NULL,
  `address` VARCHAR(250) NOT NULL,
  `iddistrict` INT(11) NOT NULL,
  PRIMARY KEY (`emiscode`),
  INDEX `fk_school_zone1_idx` (`idzone` ASC),
  INDEX `fk_school_district1_idx` (`iddistrict` ASC),
  CONSTRAINT `fk_school_district1`
    FOREIGN KEY (`iddistrict`)
    REFERENCES `snlis`.`district` (`iddistrict`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_school_zone1`
    FOREIGN KEY (`idzone`)
    REFERENCES `snlis`.`zone` (`idzone`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`lwd_belongs_to_school`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`lwd_belongs_to_school` (
  `idlwd` INT(11) NOT NULL,
  `emiscode` INT(11) NOT NULL,
  `year` YEAR NOT NULL,
  PRIMARY KEY (`idlwd`, `emiscode`, `year`),
  INDEX `fk_lwd_has_school_lwd1_idx` (`idlwd` ASC),
  INDEX `fk_lwd_has_school_school1_idx` (`emiscode` ASC),
  CONSTRAINT `fk_lwd_has_school_lwd1`
    FOREIGN KEY (`idlwd`)
    REFERENCES `snlis`.`lwd` (`idlwd`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_lwd_has_school_school1`
    FOREIGN KEY (`emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table was created to cater for the case where a learner transferred from one school to another. Therefore, it keeps track of the year in which a learner belongs/belonged to a particular school';


-- -----------------------------------------------------
-- Table `snlis`.`lwd_has_disability`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`lwd_has_disability` (
  `idlwd` INT(11) NOT NULL,
  `iddisability` INT(11) NOT NULL,
  `assessed_by` SET('special needs teacher','ordinary teacher','health personnel','parents','community leader') NOT NULL,
  `date_assessed` DATE NOT NULL,
  `identified_by` SET('special needs teacher','ordinary teacher','health personnel','parents','community leader') NOT NULL,
  `identification_date` DATE NOT NULL COMMENT 'Here, \'from birth\' will be inferred from the pupil\'s date of birth',
  `case_description` VARCHAR(150) NULL DEFAULT NULL,
  `idlevel` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`idlwd`, `iddisability`),
  INDEX `fk_lwd_has_disability_disability1_idx` (`iddisability` ASC),
  INDEX `fk_lwd_has_disability_lwd1_idx` (`idlwd` ASC),
  INDEX `fk_lwd_has_disability_disability_has_level1_idx` (`idlevel` ASC, `iddisability` ASC),
  CONSTRAINT `fk_lwd_has_disability_disability1`
    FOREIGN KEY (`iddisability`)
    REFERENCES `snlis`.`disability` (`iddisability`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_lwd_has_disability_disability_has_level1`
    FOREIGN KEY (`idlevel` , `iddisability`)
    REFERENCES `snlis`.`disability_has_level` (`idlevel` , `iddisability`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lwd_has_disability_lwd1`
    FOREIGN KEY (`idlwd`)
    REFERENCES `snlis`.`lwd` (`idlwd`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`performance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`performance` (
  `std` INT(1) NOT NULL,
  `year` YEAR NOT NULL,
  `term` INT(1) NOT NULL,
  `grade` SET('0-40','41-50','51-65','66-75','75-100') NULL DEFAULT NULL,
  `position` INT(3) NULL DEFAULT NULL,
  `idlwd` INT(11) NOT NULL,
  `emiscode` INT(11) NOT NULL,
  `teachercomment` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`std`, `year`, `term`, `idlwd`, `emiscode`),
  INDEX `fk_performance_lwd1_idx` (`idlwd` ASC),
  INDEX `fk_performance_school1_idx` (`emiscode` ASC),
  CONSTRAINT `fk_performance_lwd1`
    FOREIGN KEY (`idlwd`)
    REFERENCES `snlis`.`lwd` (`idlwd`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_performance_school1`
    FOREIGN KEY (`emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table will track the learner\'s performance through  primary school. performance is recorded for each school, year, std and term';


-- -----------------------------------------------------
-- Table `snlis`.`room_state`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`room_state` (
  `room_id` VARCHAR(2) NOT NULL,
  `emiscode` INT(11) NOT NULL,
  `year` YEAR NOT NULL,
  `enough_light` ENUM('yes','no') NOT NULL,
  `enough_space` ENUM('yes','no') NOT NULL,
  `adaptive_chairs` INT(3) NOT NULL,
  `accessible` ENUM('yes','no') NOT NULL,
  `enough_ventilation` ENUM('yes','no') NOT NULL,
  `other_observations` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Any other observations the user might want to make abou the state of this room',
  PRIMARY KEY (`room_id`, `emiscode`, `year`),
  INDEX `fk_facility_state_school1_idx` (`emiscode` ASC),
  CONSTRAINT `fk_facility_state_school1`
    FOREIGN KEY (`emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table describes the state of each facility (classrooms or resource center) at a school each year';


-- -----------------------------------------------------
-- Table `snlis`.`school_exit`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`school_exit` (
  `reason` SET('completed','sickness','death','pregnancy','distance','poor facilities','other') NOT NULL,
  `other_reason` VARCHAR(20) NULL DEFAULT NULL COMMENT 'This column is required if person chose \'other\' from reason.',
  `school_emiscode` INT(11) NOT NULL,
  `lwd_idlwd` INT(11) NOT NULL,
  `year` YEAR NOT NULL,
  PRIMARY KEY (`school_emiscode`, `lwd_idlwd`, `year`),
  INDEX `fk_dropout_school1_idx` (`school_emiscode` ASC),
  INDEX `fk_dropout_lwd1_idx` (`lwd_idlwd` ASC),
  CONSTRAINT `fk_dropout_lwd1`
    FOREIGN KEY (`lwd_idlwd`)
    REFERENCES `snlis`.`lwd` (`idlwd`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_dropout_school1`
    FOREIGN KEY (`school_emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `snlis`.`school_has_need`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`school_has_need` (
  `emiscode` INT(11) NOT NULL,
  `idneed` INT(11) NOT NULL,
  `date_procured` DATE NOT NULL COMMENT 'The date that the need became available at the school',
  `year` YEAR NOT NULL,
  `state` SET('good','average','bad') NULL DEFAULT NULL COMMENT 'This attribute indicates the state of ',
  PRIMARY KEY (`emiscode`, `idneed`),
  INDEX `fk_school_has_need_need1_idx` (`idneed` ASC),
  INDEX `fk_school_has_need_school1_idx` (`emiscode` ASC),
  CONSTRAINT `fk_school_has_need_need1`
    FOREIGN KEY (`idneed`)
    REFERENCES `snlis`.`need` (`idneed`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_school_has_need_school1`
    FOREIGN KEY (`emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = 'This table will identify which of the disability needs is being met by a particular school';


-- -----------------------------------------------------
-- Table `snlis`.`snt`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`snt` (
  `idsnt` VARCHAR(20) NOT NULL COMMENT 'Special needs teacher\n',
  `sfirst_name` VARCHAR(25) NOT NULL,
  `slast_name` VARCHAR(25) NOT NULL,
  `sinitials` VARCHAR(25) NOT NULL,
  `s_dob` DATE NOT NULL,
  `s_sex` ENUM('M','F') NOT NULL,
  `qualification` SET('certificate','diploma','degree') NOT NULL,
  `speciality` SET('HI','VI','LD') NOT NULL,
  `year_started` YEAR NOT NULL,
  PRIMARY KEY (`idsnt`))
ENGINE = InnoDB
AUTO_INCREMENT = 6
DEFAULT CHARACTER SET = utf8
COMMENT = 'Special Needs Teacher\n';


-- -----------------------------------------------------
-- Table `snlis`.`school_has_snt`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `snlis`.`school_has_snt` (
  `emiscode` INT(11) NOT NULL,
  `idsnt` VARCHAR(20) NOT NULL,
  `year` YEAR NOT NULL,
  PRIMARY KEY (`idsnt`, `emiscode`, `year`),
  INDEX `fk_school_has_snt_snt1_idx` (`idsnt` ASC),
  INDEX `fk_school_has_snt_school1_idx` (`emiscode` ASC),
  CONSTRAINT `fk_school_has_snt_school1`
    FOREIGN KEY (`emiscode`)
    REFERENCES `snlis`.`school` (`emiscode`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `fk_school_has_snt_snt1`
    FOREIGN KEY (`idsnt`)
    REFERENCES `snlis`.`snt` (`idsnt`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;