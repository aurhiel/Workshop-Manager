Workshop Manager
==================================

## Misc. information

```bash
# Connect to MySQL (pass: dbrootpw)
mysql -u root -p sf_workshop_manager

# [PROD] Connect to an OVH server
ssh a****p-a****n@ftp.cluster***.hosting.ovh.net
```


## Create a new satisfaction survey

```bash
# Command to create a new survey
## bin/console survey:create 'SURVEY_LABEL' 'SURVEY_SLUG'
bin/console survey:create 'Questionnaire VSI #1' 'vsi-1'
```

```bash
# Command to add some steps to a survey
## bin/console survey:add:step ID_SURVEY 'STEP_LABEL' STEP_POSITION
bin/console survey:add:step 1 'Contenu global de la prestation' 1
bin/console survey:add:step 1 'Animation' 2
bin/console survey:add:step 1 'Conditions Matérielles' 3
bin/console survey:add:step 1 'Appréciation globales' 4
```

```bash
# Command to add grading scales
## bin/console survey:add:grade ID_SURVEY 'GRADE_LABEL' GRADE_VALUE GRADE_POSITION
bin/console survey:add:grade 1 'Très satisfaisant' '5' 5
bin/console survey:add:grade 1 'Satisfaisant' '4' 4
bin/console survey:add:grade 1 'Peu satisfaisant' '3' 3
bin/console survey:add:grade 1 'Insatisfaisant' '2' 2
bin/console survey:add:grade 1 'Totalement insatisfaisant' '1' 1
```

```bash
# Command to add some questions to a step
## bin/console survey:add:grade Q_ID_STEP 'Q_LABEL' Q_POSITION
### Step #1
bin/console survey:add:question 1 "Durée totale" 1
bin/console survey:add:question 1 "Programme" 5
bin/console survey:add:question 1 "Apport de connaissances théoriques" 10
bin/console survey:add:question 1 "Apport de connaissances pratiques" 15
bin/console survey:add:question 1 "Adéquation avec l'objectif initial de la Prestation" 20
### Step #2
bin/console survey:add:question 2 "Pédagogie – clarté de l'exposé" 1
bin/console survey:add:question 2 "Disponibilité et réponses aux attentes" 5
bin/console survey:add:question 2 "Supports utilisés" 10
bin/console survey:add:question 2 "Interactions dans le groupe, vie de groupe" 15
### Step #3
bin/console survey:add:question 3 "Locaux" 1
bin/console survey:add:question 3 "Taille du groupe" 5
bin/console survey:add:question 3 "Matériel mis à disposition" 10
bin/console survey:add:question 3 "Organisation générale" 15
bin/console survey:add:question 3 "Disponibilité, diffusion informations" 20
### Step #4
bin/console survey:add:question 4 "Avis global Modules du parcours socle" 1
bin/console survey:add:question 4 "Avis Module carte 1 : Dim individuelle" 5
bin/console survey:add:question 4 "Avis Module carte 2 : Dim collective" 10
bin/console survey:add:question 4 "Avis global sur la prestation" 15
bin/console survey:add:question 4 "Cette prestation vous a-t-elle permis de repérer vos qualités professionnelles ?" 20
bin/console survey:add:question 4 "Cette prestation vous a-t-elle permis d'engager un travail sur vous-même ?" 25
bin/console survey:add:question 4 "Pensez-vous avoir progressé ?" 30
bin/console survey:add:question 4 "Pensez-vous avoir besoin d’approfondir les notions abordées ?" 35
```
