<p align="center"><a href="https://insurancemarket.ae" target="_blank"><img src="https://insurancemarket.ae/_next/image/?url=%2Fassets%2Fimg%2Flogo%2Flogo.png&w=2048&q=75" width="400"></a> </p>

## About Blanka - IMCRM

Insurance Market CRM (IMCRM) is the CRM used by internal team to handle the incoming leads, transactions, view or generate quotes for customers, generate car valuation, and manage customers. Basically its a complete solution for the back office to handle incoming customer inquiries and process their insurnace quotes.

IMCRM is developed on Laravel using PHP 8.0.1 or above, MariaDB 10.x or above.

URLs: 

- [Live](https://imcrm.alfred.ae/)
- [Stage](https://imcrmstage.alfred.ae/)
- [UAT](https://imcrmuat.alfred.ae)
- [DEV](https://imcrmdev.alfred.ae) [DEV1](https://imcrmdev01.alfred.ae) [DEV2](https://imcrmdev02.alfred.ae)
- [TEST](https://imcrmtest.alfred.ae)
- [Laravel Code Standard Guide Lines](https://xqsit94.github.io/laravel-coding-guidelines/)
- [Team Engineering Guide](https://github.com/InsuranceMarket-ae/engineering/wiki)

Please make sure to go through the last two lines to familiarize yourself with the code quality guide before you start contributing.

## Setting up Laravel

- Clone the repo in a new folder and use the develop branch for initial setup.
- Make sure you're using PHP 8.1 or above, Maria DB server 10.x or above on your local machine.
- Install and configure Doppler CLI, we use [Doppler](https://doppler.com/) to handle the `ENV` variables and do not use .env file. Ask the team mate to setup your doppler profile in your name with credentials.
- Ask for a dump of stage DB which you will need to import.
- Run `composer install` to install the laravel required files..

**Doppler Configuration**

Once you have doppler CLI installed on your local machine, run the following command to initiate the login setup.

- `doppler setup` This will initiate the login process, use Google login. Once logged in you will be prompted to select the profile you want to use the environment variables. You can run the command again to switch profile.
- `doppler secrets` This will show you the current environment variables.
- `composer serve` This will initiate the local server on your machine using th doppler env variables.
- `doppler run -- php artisan <any command>` will run the laravel commands using your doppler configured profile.

**Assets Configuration**

This repository use Laravel Vite to build assets like CSS and JavaScript, use the following commands once modifying these files. Yarn will be our default package manager.

- Run `yarn` so all the necassary packages are installed
- Run `yarn prod` to generate a production build
- Run `yarn dev` to hot load the changes as you make them while development.

Make sure to run `yarn prod` before every push so the assets are compiled with the updates.

**Code Formatting**

This project has pint configured, make sure to run `composer pint:fix` before every push to the code repo.

**Getting Deployment Logs**

If any deployment fails on any environment, make sure to check the deployment logs. An email will be received in your inbox with the release ID of the deployment. Use that Release ID and go into postman collection `Logs -> Release Logs`, replace the release ID in the URL, click on the arrow next to Send button and click Send and Download.

**Redis Allocated DBs**

- Prod - 15
- Stage - 2
- UAT - 3
- UAT2 - 5
- DEV - 4
- DEV01 - 7
- DEV02 - 8
- Test - 9

## Contributing

- `develop` branch is the main coding branch where new branches will be generated from, and PRs will be merged into. Same branch is connected with stage for testing and QA.
- `main` branch is used for production deployments.

## Database Migration

This project is not used for running migrations.

This repo is integrated with [dhalsim](https://github.com/InsuranceMarket-ae/dhalsim); on each env deployment, the relelvant branch from dhalsim is triggered to run the migrations from before blanka deployment.

# Running Project Locally with Docker

## Introduction

This guide outlines the steps to run the project locally on Docker. Docker provides a consistent environment for development and deployment, ensuring that the project runs smoothly across different systems.

## Prerequisites

Before proceeding, ensure that you have the following prerequisites installed on your system:

- Doppler
- Docker
- Docker Compose

## Configuration

The provided configuration is a basic setup that you can customize according to your requirements. Some ports may already be in use by other services on your system, so you may need to mirror those ports using Docker, these environment variable are necessary it will help you configure according to your need. You can use the "localenvs_docker" which already have these keys set by default at Doppler, There is already configuration available on doppler "localenvs_docker" if you configure with this env configuration everything run smoothly

### Example Configuration

```dotenv
# App port
APP_URL=http://localhost:8000
WEB_PORT=8000

# Doppler env token
DOPPLER_TOKEN_IMCRM=DOPPLER_TOKEN

# Nginx Port
SSL_PORT=452

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=dbblanka:3306
DB_PORT=3306
DB_MIRROR_PORT=3313
DB_DATABASE=blanka_db
DB_USERNAME=root
DB_PASSWORD=root

# Mailhog Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhogblanka
MAIL_PORT=1032
MAIL_UI_PORT=7025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Phpmyadmin Port
PHPMYADMIN_PORT=2600
```

## Container Names

- mailhogblanka
- dbblanka
- appblanka
- phpmyadminblanka
- mailhogblanka

## Building and Running Containers

Use the following commands to build and run the containers for your services for build you need to run with doppler cause it will passed down doppler token from env to your containers:

```dotenv
doppler run -- docker-compose -f docker-compose-local.yml build
```

This command builds the containers related to your services, here -f flag detonates which dockerfile you want to use as there are some production docker files as well so its better to mention which file to use otherwise it will pick by default file which is docker-compose.yml .

## Run Containers:

```dotenv
doppler run -- docker-compose -f docker-compose-local.yml up
```

This command starts your services. If you add the -d flag, it will run in the background. If your containers are not working as expected, try running without the -d flag to see the logs and errors in the console.

## Execute Commands Inside Containers:

```dotenv
doppler run -- docker-compose -f docker-compose-local.yml exec container-name-here-like-(appblanka) php artisan optimize
doppler run -- docker-compose -f docker-compose-local.yml exec container-name-here-like-(appblanka) php artisan migrate/make:controller
doppler run -- docker-compose -f docker-compose-local.yml exec container-name-here-like-(appblanka) php artisan make:controller
doppler run -- docker-compose -f docker-compose-local.yml exec container-name-here-like-(appblanka) /bin/bash ----------- you can access php container
doppler run -- docker-compose -f docker-compose-local.yml exec container-name-here-like-(dbblanka) /bin/bash ----------- you can access mysql container
```

This command allows you to run commands inside your container. You can also enter the container using bin/bash instead of php/mysql command.

## Important Note

I have created separate Docker files and configurations for local environments. The docker-local folder contains configurations for local Docker, including files "Dockerfile-Local" and "docker-compose-local.yml", which are used to build and run images for local development.

## Access containers application on your local browser according default docker config from doppler

- appblanka --------- you can access application http://127.0.0.1:8000
- phpmyadminblanka -- you can access phpmyadmin http://127.0.0.1:2600
- mailhogblanka ----- you can access mailhog http://127.0.0.1:7025

## Conclusion

By following these steps, you can easily run the project locally on Docker. Docker provides a convenient and isolated environment for development, ensuring consistency across different systems. If you encounter any issues, refer to the Docker documentation or seek assistance from the project team
