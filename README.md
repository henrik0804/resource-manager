# Resource Manager

Dummy project for Tim's Bachelor thesis study on vibe-coding.

## Project Description

The Resource Manager is a web application designed to centralize resource and task scheduling. It addresses the common problem of manual resource management using spreadsheets, which often leads to double bookings, overloads, and high organizational effort.

### Key Features

- **Resource Management** - Create, edit, and delete resources (employees, rooms, etc.)
- **Task Planning** - Record tasks with start/end dates, effort, and priority
- **Manual & Automated Assignment** - Assign tasks manually or automatically based on availability and qualifications
- **Conflict Detection** - Warnings for overloaded or double-booked resources with resolution suggestions
- **Priority-Based Scheduling** - Higher priority tasks are scheduled preferentially
- **Visual Overview** - Calendar/Gantt view for tasks and resources
- **Utilization View** - Resource utilization over variable time periods
- **Role Management** - Different user roles (project manager, employee) with appropriate permissions

## Development

```bash
# Install dependencies
composer install
npm install

# Run development server
composer run dev

# Run quality tools (fix mode)
composer run fix

# Run tests and checks
composer run test
```
