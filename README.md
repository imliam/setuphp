# SetuPHP

Get up-and-running with a new PHP project in seconds.

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

Running the the `setuphp` command in an existing project directory will send you through a series of prompts to configure your project. You can also run commands individually:

```bash
setuphp
```

### Git

Sets up a new local and remote git repository and commit the project's files.

```bash
setuphp git
```

_Note: if you have the [GitHub CLI](https://cli.github.com/) installed, SetuPHP will let you use that to set up the remote repository automatically._
