# Oregon Theater Project Prototype Site
This repository includes all the files needed to replicate the Oregon Theater Project website on your server or local Docker.

The site is built in Drupal 9.x with PHP 8.1 and above. Please refer to this link for more details about the Drupal system requirements: [https://www.drupal.org/docs/system-requirements](https://www.drupal.org/docs/system-requirements)

The live website can be viewed here: [https://oregontheaterproject.uoregon.edu](https://oregontheaterproject.uoregon.edu)

*Instructions are for a Linux server

## Installation

This repo provides all files needed to get the site launched in your preferable environment.

How to use those files depends on your Drupal environment.

### Clone the Project Repo

``` bash
git clone https://github.com/caspages/otp.git
```

### Option 1: Drupal

If you have your local LAMP enviroment, please follow the Drupal installation guide here: [https://www.drupal.org/docs/installing-drupal](https://www.drupal.org/docs/installing-drupal). The composer file is also provided in this repo (in `site/`).

#### Import Database

The database dump file is in directory `sql-import-files`.

Please find a tool or use `mysqldump` utility to get the site data ready.

### Option 2: Docker

The Dockerfile references the Drupal official Docker image from Docker Hub. The database info and settings are in the `docker-compose.yml` file.

To use `docker-compose` or `docker compose`, please refer to this document: [docker-compose vs docker compose](https://docs.docker.com/compose/migrate/)

#### First time lunch the site

If it's your first time to lunch the site, it's recommended to use the command below because it will show the build-up process given that the composer build will take about 30 minutes depending on your network.

``` bash
docker compose up
```

#### Non-first time lunch the site

If you already build up the site using the command above, you should be able to lunch the container with no need to watch the process. Use the command below to run lunch the container in the background.

``` bash
# Use -d to run a Container that is detached in the background.
docker compose up -d
```

#### Import Database

The database dump file is in directory `sql-import-files`.

If you use Docker installation, you don't need to care about this. The Docker compose file already takes care of database import automatically.

## Site Launch

If you follow the traditional Drupal installation, please get yourself to know which URL (with port) is for the site.

If you use Docker to run the site, please use http://localhost:8080/ to browser the site.

### Login Credentials

Username: admin

Password: example_admin

## Custom Modules

We created a custom module `Leaflet Map Timeline` to visualize the timeline map in the homepage. The main control is built on the JavaScript code and JSON API endpoint. This JS file will be the main one you will need to edit.

```
\otp.uoregon.edu\modules\leaflet_map_timeline\map.timeline.drupal.js
```

Replace `film-roll.png` in the JS file with some icon you want to use. It is the theater marker in the timeline map.

## Custom Styling and JavaScript

One chunk of CSS and JavaScript files stay in the module `Leaflet Map Timeline` directory. The other one is managed by the module `Asset Injector`. Site administrator can go to `your_site/admin/config/development/asset-injector` to find all injected styling and JavaScript codes.

To be noticed, we also defined some icons in the `Asset Injector -> CSS Injector -> Blog icons`. Those icons show in the Article create page.

## Theme

The orginal Oregon Theater Project site purchased a commercial theme called `Corporate Plus` which can not be shared for free according to the agreement. In this repo, a free version `Corporate Lite` replaces the commercial one.

You can also use any themes you have to replace the default one.
