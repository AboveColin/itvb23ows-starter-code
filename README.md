# OWS Project

## Overzicht
Dit project is opgezet met Docker-compose en omvat meerdere services, waaronder een applicatie server, een database server (MySQL), een AI-service, Jenkins voor continuous integration en delivery, en SonarQube voor codekwaliteit analyse.

## Vereisten
Voordat je begint, zorg ervoor dat Docker en Docker Compose geïnstalleerd zijn op je systeem. Daarnaast moet de AI-code gekloond worden van de volgende Git repository: [https://github.com/AboveColin/itvb23ows-hive-ai](https://github.com/AboveColin/itvb23ows-hive-ai).

## Configuratie
1. Clone de AI-code naar de `AI` map in je projectdirectory.
2. Stel het MySQL-wachtwoord in het `docker-compose.yml` bestand in. Dit moet zowel onder de `app` als onder de `db` service gedaan worden.
3. Controleer of de hostname en poort voor de AI-service correct zijn ingesteld. Pas deze indien nodig aan.
4. Mogelijk moet je in de App folder nog de commando ``composer install`` uitvoeren.

## Opstarten
Om de services te starten, open je een terminal in de projectdirectory en voer je het volgende commando uit:
```bash
docker compose up -d
```

## Diensten
- App: Een webapplicatie draaiend op poort 8000.
- DB: Een MySQL-database beschikbaar op poort 3306.
- AI: Een AI-service beschikbaar op poort 5001.
- Jenkins: Een Jenkins-server beschikbaar op poort 8080 voor CI/CD.
- SonarQube: Een SonarQube server op poort 9000 voor codeanalyse.

## Opslag
Docker volumes worden gebruikt voor het persistent maken van data voor MySQL, Jenkins en SonarQube. Deze volumes worden automatisch aangemaakt bij het starten van de services.

