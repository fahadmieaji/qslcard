# QSL Card Web Application

## Overview
This project is a web application designed for managing and generating QSL cards, which are confirmation cards used in amateur radio communications. It provides functionalities likely including user management, QSL card design/templating, and logging capabilities.

## Features
-   **User Authentication:** Handles user registration, login, profile management, and password changes.
-   **QSL Card Generation/Designer:** Allows users to design and generate QSL cards.
-   **Logbook Functionality:** Manages amateur radio contact logs, potentially including upload capabilities.
-   **Template Management:** Provides tools to manage and save QSL card templates.
-   **File Uploads:** Supports uploading of background images and profile pictures.
-   **Configuration Options:** Contains application-wide configuration settings.
-   **Database Integration:** Handles database connections and operations.
-   **Utility Functions:** Provides common utility functions for the application.
-   **ADIF File Handling:** Likely for importing or exporting Amateur Data Interchange Format (ADIF) files, common in amateur radio.
-   **LOTW Integration:** Suggests integration with ARRL's Logbook of the World for contact confirmation.

## Installation

### Prerequisites
-   Web server (e.g., Apache, Nginx)
-   PHP (ensure required extensions are enabled, version compatible with the application)
-   MySQL or compatible database server
-   Composer (recommended for PHP dependency management, if applicable)

### Setup Steps
1.  **Clone the repository:**
    ```bash
    git clone https://github.com/fahadmieaji/qslcard.git
    cd qslcard
    ```
2.  **Web Server Configuration:**
    *   Configure your web server (e.g., Apache Virtual Host) to point its document root to the `public/` directory. This ensures only public-facing files are directly accessible.
    *   Ensure PHP is correctly configured and enabled for your web server.
3.  **Database Setup:**
    *   Create a new MySQL database and a dedicated user for the application.
    *   Update `config/config.php` with your database connection details (host, username, password, database name).
    *   **Database Schema:** *Please provide the SQL file for the database schema or instructions on how to initialize the database (e.g., if `public/install.php` handles it automatically).*
4.  **PHP Dependencies (if applicable):**
    *   If the project uses Composer, run `composer install` in the project root to install dependencies. (Please confirm if a `composer.json` file exists or is needed).
5.  **Access the application:**
    *   Open your web browser and navigate to the configured domain or IP address.
    *   Follow any on-screen installation instructions if presented (e.g., by visiting `http://your-domain/install.php` initially).

## Usage
*   **User Registration & Login:** Create an account and log in to access your personalized dashboard.
*   **Design QSL Cards:** Utilize the integrated designer to create and customize QSL cards with various layouts and data.
*   **Manage Logbook:** Input, view, and manage your amateur radio contact logs.
*   **Upload Files:** Upload images for use in QSL card designs or profile pictures.

## Developer
-   **Name:** Abdullah Al Fahad
-   **Email:** fahadmieaji@gmail.com
