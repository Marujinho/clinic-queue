# Agent guidelines

This repository is a Laravel 13 + Livewire 4 application. See `README.md` for the
product overview and setup, and `docs/STYLEGUIDE.md` for the UI design system.

## Project conventions

### UI / Component rule

All Blade and Livewire UI components MUST follow the style guideline at `docs/STYLEGUIDE.md` — its color tokens, Switzer typography, spacing/radius/shadow values, and component specs. Do not introduce ad-hoc colors or fonts (e.g. arbitrary `text-[#…]` classes) outside the documented tokens.

Any new or changed UI component MUST be registered in the **Component Catalog** section of `docs/STYLEGUIDE.md` in the same change. A component is not "done" until it is documented there. If a design need is not covered by an existing token or spec, extend `docs/STYLEGUIDE.md` first, then build.
