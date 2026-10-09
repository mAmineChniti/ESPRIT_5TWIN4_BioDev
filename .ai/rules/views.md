---
paths:
  - 'resources/views/**'
---

# Views

## Back office vs front office views
Blade views mirror the route URI under an office folder, Next.js App Router style: back/ is the back office (every page requires login, uses layouts.back) and front/ is the front office (visitor pages, uses layouts.front); auth screens live at front/auth/ with the guest layout because visitors see them. Path after the office equals the URI segments: back/logistics/shipments/index.blade.php serves /logistics/shipments, front/products/show.blade.php serves /products/{food}. Use only index/show/create/edit (+ form/partials co-located in the same folder); one view file per route with shared bodies in partials, never render across offices (no front route renders back.*). Shared UI goes in components/ or layouts/, never duplicated per route. When adding or renaming a route, create/move the view to the mirrored path in the same change. Sanctioned exceptions: / renders front.home, and /regions stays back/regions (back-office shorthand for the public /agricultural-regions).

## Server-side validation owns every form
Every data-submitting form carries novalidate so the browser never blocks or pre-validates; the server is the sole authority via FormRequest or $request->validate(), and failures redirect back with errors rendered next to their field (input-error/@error with aria-describedby, role=alert). Keep type=/required attributes as UX hints only. Validate hidden and forged inputs too, never reading unvalidated values off the request.
