# ZWave-Health-Jeedom
Script d'affichage des informations de santé ZWave sur le dashboard Jeedom.

# Paramètres Obligatoires:

1. Adresse du Jeedom (si local: 127.0.0.1)
2. Clé API du Jeedom
	- **Attention si le Jeedom est en slave, indiqué la clé API du master.**
3. Mode d'affichage sur le dashboard: 
	- 0 : Modules Deads 
	- 1 : Modules Timeout + Dead 
	- 2 : Tout les modules

# Paramètres Falculatifs:

- 4: Port daemon ZWave (si non renseigné: 8083)
- 5: Dossier pour la sauvegarde du fichier JSON, par défaut /tmp
	- **Attention pour utiliser ce paramètre il faut obligatoirement indiqué le paramètre 4**

#TODO

- Améliorer l'affichage
- Support des modules Piles

