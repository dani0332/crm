<p align="center"><a href="https://insurancemarket.ae" target="_blank"><img src="https://insurancemarket.ae/_next/image/?url=%2Fassets%2Fimg%2Flogo%2Flogo.png&w=2048&q=75" width="400"></a> </p>  

## About Blanka - IMCRM
Insurance Market CRM (IMCRM) is the CRM used by internal team to handle the incoming leads, handle rewards,transactions, view or generate quotes for customers, generate car valuation, and manage customers. Basically its a complete solution for the back office to handle incoming customer inquiries and process their insurnace quotes.

IMCRM is developed on Laravel using PHP 8.0.1 or above, MariaDB 10.x or above. 

URLs: 

- [Live](https://crm.alfred.ae/)
- [Stage](https://crmstage.alfred.ae/)
- [UAT](https://crmuat.alfred.ae)
- [DEV](https://crmdev.alfred.ae) [DEV1](https://crmdev01.alfred.ae) [DEV2](https://crmdev02.alfred.ae)
- [Laravel Code Standard Guide Lines](https://xqsit.github.io/laravel-coding-guidelines/)
- [Team Engineering Guide](https://github.com/InsuranceMarket-ae/engineering/wiki)

Please make sure to go through the last two lines to familiarize yourself with the code quality guide before you start contributing.

## Setting up Laravel

- Clone the repo in a new folder and use the develop branch for initial setup.
- Make sure you're using PHP 8.0.1 or above, Maria DB server 10.x or above on your local machine.
- Install and configure Doppler CLI, we use [Doppler](https://doppler.com/) to handle the ```ENV``` variables and do not use .env file. Ask the team mate to setup your doppler profile in your name with credentials.
- Ask for a dump of stage DB which you will need to import.
- Run ```composer install``` to install the laravel required files.


**Doppler Configuration**

Once you have doppler CLI installed on your local machine, run the following command to initiate the login setup.
- ```doppler setup``` This will initiate the login process, use Google login. Once logged in you will be prompted to select the profile you want to use the environment variables. You can run the command again to switch profile.
- ```doppler secrets``` This will show you the current environment variables.
- ```composer serve``` This will initiate the local server on your machine using th doppler env variables.
- ```doppler run -- php artisan <any command>``` will run the laravel commands using your doppler configured profile.

**Assets Configuration**
This repository use Laravel Mix to build assets like CSS and JavaScript, use the following commands once modifying these files. Yarn will be our default package manager.

- Run ```yarn``` so all the necassary packages are installed
- Run ```yarn run prod``` to generate a production build
- Run ```yarn watch``` to hot load the changes as you make them while development.

## Contributing
- ```develop``` branch is the main coding branch where new branches will be generated from, and PRs will be merged into. Same branch is connected with stage for testing and QA.
- ```main``` branch is used for production deployments.

