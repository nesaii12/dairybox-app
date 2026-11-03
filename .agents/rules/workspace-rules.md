---
trigger: always_on
---

# Workspace Rules

Always adhere to the guidelines defined in [.agent/agent.md](file:///c:/Users/basco/Devs/dairybox/.agent/agent.md):
# Workspace Rules

## Git Workflow Rule
- *Never automatically commit or push*: Do NOT run git commit or git push automatically.
- *Provide Manual Commands*: Upon completing any feature, fix, or code modification, provide the user with suggested git commands and commit messages so the user can manually execute them in their terminal.

## Coding Principle
Act like a senior full-stack developer: understand the existing codebase first, reuse what already exists, and only change what is necessary to solve the task. **Seeder-Safety Exception: Never modify, reset, delete, recreate, or run database seeders/migrations that may alter existing data unless I explicitly ask you to do so; if a task requires one, stop and ask for my permission first.** Keep the code clean, simple, secure, maintainable, and production-ready without over-engineering or adding unnecessary code.

## Data Integrity & Authenticity Principle (Strictly No Hardcoded Sample Data)
Never hardcode mock, demo, placeholder, or synthetic sample data in application layouts, pages, components, or fallback operators (e.g., fake prices like "₱65.00", synthetic stock/batch counts like `|| 50`, dummy expiration dates, artificial ratings like "★ 5.0 (120 reviews)", mock delivery addresses like "Brgy. Sample", or static dashboard metrics). All UI content across storefront, inventory, orders, deliveries, and reports must be strictly data-driven and dynamically sourced from the live database. When data is empty, pending, or absent, display clean, professional, authentic empty states (e.g., "No products available yet", "No inventory batches registered") or omit the element gracefully instead of inventing or falling back to hardcoded sample values.

## UI & Design Principle (Humanized & Clutter-Free)
Design and build humanized, realistic, and polished interfaces. Avoid gimmicky or unnecessary indicators (e.g., "Live sync", "Auto-saving...", artificial status badges, robot-like meta labels, or placeholder tags). Every element on screen must serve a genuine, practical purpose for real users, keeping the interface clean, intuitive, natural, and distraction-free.