# SetuPHP

Get up-and-running with a new PHP project in seconds. SetuPHP is a CLI tool designed to scaffold and streamline new PHP projects by setting up Git repositories, CI workflows, and common development tooling—all through a series of interactive prompts.

## Features

- **Git Integration:**
  Initialize a local Git repository, create an initial commit, and set up a remote repository. If the [GitHub CLI](https://cli.github.com/) is installed, SetuPHP can automatically create a remote repository for you.

- **CI Workflow Setup:**
  Automatically generate CI configuration files for GitHub Actions or GitLab CI by detecting your project's remote repository and available tools (such as Pest, PHPUnit, PHPStan, or Psalm).

- **Tooling Installation:**
  Install popular test frameworks (Pest or PHPUnit), browser testing tools (Laravel Dusk or Laracasts Cypress for Laravel apps), static analysis tools (PHPStan with Larastan or Psalm with its Laravel plugin), and code style tools (php-cs-fixer or Laravel Pint) based on your preferences.

- **Interactive Setup:**
  Designed to work in your existing PHP project. SetuPHP uses interactive prompts to guide you through configuring your project, from creating a `composer.json` file (if missing) to installing and configuring dependencies.

## Installation

SetuPHP requires PHP. Using Composer, you can install SetuPHP globally with the following command:

```bash
composer global require imliam/setuphp
```

Alternatively, you can use [cpx](https://cpx.dev) to run SetuPHP:

```bash
cpx imliam/setuphp
```

## Usage

Run the `setuphp` command in the root directory of your PHP project to start the interactive setup process that will walk you through everything:

```bash
setuphp
```

Alternatively, you can run individual commands if you want to focus on only certain steps:

```bash
setuphp git
setuphp ci
setuphp tooling
```
