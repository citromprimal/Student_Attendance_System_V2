# 🎓 Student Attendance System V2

![Project Status](https://img.shields.io/badge/Status-In%20Development-yellow)
![Project Type](https://img.shields.io/badge/Project-University%20Team%20Project-blue)
![Team](https://img.shields.io/badge/Team-5%20Members-brightgreen)
![Platform](https://img.shields.io/badge/Platform-Android%20%7C%20Web-lightgrey)

A student attendance management system designed to simplify and digitalize attendance tracking at university lectures and practical classes using **NFC technology** and **ISIC student identification cards**.

## 📖 Project Overview

The goal of this project is to design and implement a system for tracking student attendance at university classes, including lectures and practical sessions.

Student identification is based on **Near Field Communication (NFC)** technology and **ISIC cards**, allowing students to register their attendance by presenting their cards to an NFC-enabled Android device.

The system consists of two interconnected components:

- **Android Application** – Responsible for reading ISIC cards through NFC, providing immediate feedback, and communicating with the server.
- **Web Application** – Responsible for the core business logic, attendance management, data processing, and integration with the Academic Information System (AIS).

In addition to attendance tracking, the system aims to support data import and export between AIS and the developed platform.

## ✨ Key Features

### 📱 Android Application
- NFC-based ISIC card scanning
- Student identification using card data
- Visual and audible feedback after scanning
- Communication with the backend through a REST API
- Handling successful and unsuccessful attendance registrations

### 🌐 Web Application
- Centralized student attendance management
- Management of lectures and practical sessions
- Attendance records and history
- Student and course data management
- Import and export of academic data
- Integration with AIS

### 🗄️ Backend & Database
- REST API for communication between applications
- Centralized data storage
- Student and ISIC card data management
- Attendance validation and processing
- Data exchange with external academic systems

*Note: The listed features represent the planned functionality and may evolve during development.*

## 🏗️ System Architecture

The system follows a client-server architecture in which the Android application acts as an NFC scanning client and the web application handles the core attendance management logic.

```text
                  ┌──────────────────────┐
                  │      ISIC Card       │
                  └──────────┬───────────┘
                             │
                             │ NFC
                             ▼
                  ┌──────────────────────┐
                  │ Android Application  │
                  │   (NFC Scanner)      │
                  └──────────┬───────────┘
                             │
                             │ REST API
                             ▼
                  ┌──────────────────────┐
                  │   Backend Server     │
                  │   Business Logic     │
                  └──────────┬───────────┘
                             │
               ┌─────────────┴─────────────┐
               ▼                           ▼
      ┌─────────────────┐       ┌─────────────────────┐
      │    Database     │       │   Web Application   │
      │ Attendance Data │       │ Attendance Overview │
      └─────────────────┘       └─────────────────────┘
               │
               │ Import / Export
               ▼
      ┌─────────────────┐
      │       AIS       │
      │ Academic System │
      └─────────────────┘
```

## 🛠️ Technologies

The project uses or plans to use the following technologies:

| Component | Technology |
|---|---|
| Mobile Application | Android, Kotlin |
| Card Identification | NFC |
| Client-Server Communication | REST API, JSON |
| Web Application | To be determined |
| Backend | To be determined |
| Database | To be determined |
| Version Control | Git, GitHub |

## 📁 Repository Structure

The project is organized as a monorepo containing both applications and shared resources.

```text
Student_Attendance_System_V2/
│
├── android/             # Android NFC scanning application
│
├── web/                 # Web application and backend
│
├── database/            # Database schemas and scripts
│
├── docs/                # Project documentation
│
└── README.md            # Project overview
```

*The repository structure may be updated as development progresses.*

## 👥 Development Team

This project is developed by a team of five university students from the **Faculty of Electrical Engineering and Information Technology (FEI STU)** at the **Slovak University of Technology in Bratislava**.

| Team Member | Email |
|---|---|
| **[Bálint Kálnay](https://github.com/Bejlint)** | [xkalnay@stuba.sk](mailto:xkalnay@stuba.sk) |
| **[Dávid Bohák](https://github.com/citromprimal)** | [xbohak@stuba.sk](mailto:xbohak@stuba.sk) |
| **[Filip Varga](https://github.com/xVarga)** | [xvargaf2@stuba.sk](mailto:xvargaf2@stuba.sk) |
| **[János Kanyicska](https://github.com/yni10)** | [xkanyicska@stuba.sk](mailto:xkanyicska@stuba.sk) |
| **[Tamás Bagin](https://github.com/Tami96b)** | [xbagint@stuba.sk](mailto:xbagint@stuba.sk) |

## 🚀 Development Status

**The project is currently under development.**

The development process includes the design, implementation, integration, and testing of the Android and web applications, database infrastructure, and academic data exchange functionality.

## 🎯 Project Objectives

The primary objectives of the project are:

1. Develop an Android application capable of reading ISIC cards using NFC technology.
2. Implement reliable communication between the Android application and the backend server.
3. Design a centralized attendance management system accessible through a web interface.
4. Store and manage student attendance records.
5. Enable academic data exchange through import and export functionality.
6. Integrate the individual components into a functional attendance management platform.

---

**Student Attendance System V2** — University Team Project | FEI STU
