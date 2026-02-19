# BiblioTech — Sistema di gestione BiblioTech

## Versioni Software

| Componente | Versione |
|------------|----------|
| PHP        | 8.2 |
| MySQL      | 8.0 |
| Apache     | 2.4 |
| Bootstrap  | 5.3 |
| PHPMailer  | 6.9 |
| Docker     | Latest |
| Docker Compose | Latest |


## Prerequisiti

- Docker Desktop installato ([https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/))
- Account Mailtrap gratuito (https://mailtrap.io)


## Istruzioni di Avvio

### 1. Scaricare il progetto

### 2. Configurare credenziali Mailtrap

Aprire il file `docker-compose.yml` e modificare le seguenti righe:

- MAILTRAP_USER=INSERISCI_USERNAME_MAILTRAP
- MAILTRAP_PASSWORD=INSERISCI_PASSWORD_MAILTRAP

Le credenziali si trovano su Mailtrap in: 
**Email Testing → Inboxes → SMTP Settings**

### 3. Avviare i container

docker compose up -d --build

### 4. Accedere all'applicazione

Aprire il browser all'indirizzo: **http://localhost:8085**

Inserire una delle seguenti email per ricevere il link di accesso:

**Studenti:**
- mario.rossi@example.com
- laura.bianchi@example.com
- giuseppe.verdi@example.com

**Bibliotecario:**
- anna.biblioteca@example.com

Controllare l'email su Mailtrap e cliccare sul link ricevuto.


## Comandi Utili

# Fermare i container
docker compose down

# Riavviare i container
docker compose restart

# Visualizzare i log
docker compose logs -f

# Ricostruire da zero
docker compose down -v
docker compose up -d --build


## Accesso Database

docker exec -it bibliotech_db mysql -u bibliotech_user -p

Password: `bibliotech_password`


## Risoluzione Problemi

**Porta 8085 già in uso:**
Modificare la porta nel file `docker-compose.yml` alla riga `ports: - "8085:80"` in `"8086:80"`

**Email non arriva:**
Verificare le credenziali Mailtrap nel file `docker-compose.yml`

**Container non si avvia:**
Eseguire `docker compose logs` per visualizzare gli errori


Progetto sviluppato Luigi Lattanzio VITIA/A - A.S. 2025/2026
