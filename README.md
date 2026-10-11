# ParkEasy – Smart Parking Management System

ParkEasy is a smart parking management project designed to make finding and managing parking spaces easier. It aims to provide users with a convenient way to explore parking information, manage bookings, and access digital parking tickets.

## About the Project

Finding available parking can be time-consuming, especially in busy areas. ParkEasy aims to simplify the parking process through an interactive web interface and digital features.

The project combines parking management functionality with an interactive map, QR-based ticketing, and an AI assistant.

## Key Features

- **Interactive Map:** Explore parking locations using an interactive map powered by Leaflet.
- **Parking Booking:** Access the parking booking workflow provided by the application.
- **QR Ticketing:** Support digital parking tickets with QR-related functionality.
- **AI Assistant:** Interact with the project's AI assistant.
- **Booking Management:** Use the available booking and confirmation pages to manage the parking workflow.
- **Web Interface:** Access the system through a browser.

*Note: Keep only the features that are implemented and working in your current version.*

## Technology Stack

Update this list to match the technologies actually used in your source code.

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Mapping:** Leaflet.js
- **Database:** Add the actual database technology used by the project
- **Development Tools:** Visual Studio Code, Git, GitHub
- **Deployment:** Vercel configuration is included in the repository; confirm that the deployed application and its backend services work correctly.

## How the System Works

1. The user opens the ParkEasy website.
2. The user explores the available parking interface and map.
3. The user follows the booking workflow supported by the application.
4. The system provides booking-related information and QR ticket functionality where implemented.
5. The user can access the other available features, including the AI assistant.

## Project Structure

```text
ParkEasy/
├── api/
├── assets/
├── config/
├── sql/
├── api_ai_chat.php
├── booked.php
├── confirmbook.php
├── index.php
├── vercel.json
└── README.md
```

*The exact files and folders may vary depending on your current project version.*

## Installation and Setup

### Prerequisites

- PHP and a compatible local development environment
- A database server, if required by the application
- A web browser
- Git (optional, for cloning the repository)

### Run Locally

1. Clone or download the repository.
2. Open the project folder in your development environment.
3. Configure the database and application settings according to the files in the `config/` and `sql/` folders, if applicable.
4. Start the appropriate PHP development server or local web server.
5. Open the local application URL in your browser.

Example using PHP's built-in development server:

```bash
php -S localhost:8080
```

Then open:

```text
http://localhost:8080/index.php
```

Use this command only if PHP is installed and the project is configured to run this way.

## Screenshots

Add screenshots of your actual working application here to help visitors understand the interface.

Suggested screenshots:
- Home page
- Interactive parking map
- Booking interface
- QR ticket
- AI assistant

## Future Improvements

- Improve parking availability updates.
- Enhance the booking and confirmation workflow.
- Strengthen application security and input validation.
- Improve mobile responsiveness and accessibility.
- Add clearer booking history and user feedback, where appropriate.

## Learning Outcomes

This project provides practical experience with web application development, PHP, frontend technologies, interactive maps, booking workflows, version control, and deployment configuration.

## Repository

GitHub: https://github.com/Daradeaarti/ParkEasy

## Author

**Aarti**

B.E. – Artificial Intelligence and Data Science

## License

Add a license only after deciding how you want others to use, modify, and distribute this project.
