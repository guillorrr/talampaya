# WordPress Theme with Timber

This WordPress theme uses Timber to handle views and frontend logic. Below is a detailed description of the directory structure and the logic behind its organization.

## Directory Structure

```
.
├── blocks # Reusable code snippets that form small but essential parts of the page.
│   ├── footer.twig
│   ├── header.twig
│   └── comment.twig
├── components # Reusable components that can be used across multiple pages or sections.
│   ├── accordion.twig
│   ├── breadcrumbs.twig
│   ├── modal.twig
│   ├── nav.twig
│   └── pagination.twig
├── includes # Scripts and code snippets included throughout the site.
│   ├── facebook-pixel.twig
│   └── google-analytics.twig
├── layouts # Base templates defining the overall structure of the site.
│   ├── base.twig
│   └── blog.twig
├── pages # Specific templates for individual pages of the site.
│   ├── 404.twig
│   ├── about.twig
│   ├── archive.twig
│   └── contact.twig
└── sections # Larger parts of the pages, such as complete content sections.
    ├── faq.twig
    ├── hero.twig
    ├── comments.twig
    └── main.twig

```

## Structure Logic

1. **Reusability and Maintenance:**
    - **`blocks/` and `components/`:** These directories store small pieces and reusable components to promote DRY (Don't Repeat Yourself) and keep the code modular and easy to maintain.
    - **`includes/`:** Scripts and snippets included in multiple parts of the site, facilitating centralized management of these codes.

2. **Template Organization:**
    - **`layouts/`:** Base templates that establish the overall structure of the pages, ensuring a consistent look and structure.
    - **`pages/`:** Specific templates for individual pages, allowing detailed customization of each page without affecting others.

3. **Independent Sections:**
    - **`sections/`:** Large and complete sections that can be assembled to build complex pages more easily and in a structured manner.

This structure allows for a more organized and modular development, facilitating code reuse and improving the maintainability of the theme in the long term.

---

## Using Timber

Timber is used to separate PHP logic from views, utilizing Twig templates to render pages. Each Twig template is located in the mentioned directories and is loaded from WordPress controllers.

For more information on how to use Timber, visit the [official Timber documentation](https://timber.github.io/docs/).
