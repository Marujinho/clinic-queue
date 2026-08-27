# Clinic Queue Management System

A web-based healthcare application designed to help clinics manage patient check-ins, appointments, and waiting queues.

Receptionists can register patients and check them into the appropriate queue, while healthcare providers can view the queue, call the next patient based on priority and arrival time, start the consultation, and mark it as completed.

The system provides a real-time queue dashboard and basic operational metrics such as waiting patients, patients currently being served, completed appointments, and average waiting time.

## Core Technologies

- Laravel
- Livewire
- MySQL
- Tailwind CSS
- Pest
- Laravel Sail

## UI & Styling

`docs/STYLEGUIDE.md` is the single source of truth for the app's visual design (color tokens, Switzer typography, spacing/radius/shadow, layout, and component specs).

**Rule:** Every Blade and Livewire UI component MUST follow `docs/STYLEGUIDE.md` and MUST be registered in that file's **Component Catalog** in the same change that adds or modifies it. Do not use ad-hoc colors or fonts outside the documented tokens. Extend the guide first if a need isn't covered, then build.
