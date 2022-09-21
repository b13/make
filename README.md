# Make - A TYPO3 extension to kickstart extensions and components

This TYPO3 extension allows to easily kickstart new TYPO3 extensions
and components, such as Middlewares, Commands or Event listeners, by
using an intuitive CLI approach.

TYPO3 Explained offers an extended tutorial on how to
[Kickstart a TYPO3 Extension with Make](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Tutorials/Kickstart/Make/Index.html).

## Installation

Install this extension as "dev" dependency via `composer req b13/make --dev`.

You can also download the extension from the
[TYPO3 Extension Repository](https://extensions.typo3.org/extension/make/)
and activate it in the Extension Manager of your TYPO3 installation.

Note: This extension is compatible with TYPO3 v10, v11 and v12 and should
only be used in development context. So please make sure it is excluded
for production releases.

## Usage

All components, including new extensions, can be created with
a dedicated command, executed on CLI with the ```typo3``` binary:
`bin/typo3 make:<component_name>`.

Example for creating a new extension:

```bash
bin/typo3 make:extension
```

All commands are interactive, which means you have to configure the
extension or component by answering the displayed questions. Most of
them automatically suggest a best practice default value, e.g. for
identifiers or namespaces, which can just be confirmed.

It's also possible to customize those default values using environment
variables with the `B13_MAKE_` prefix. The full list is shown below:

- `B13_MAKE_BACKEND_CONTROLLER_DIR` - Default directory for backend controllers
- `B13_MAKE_BACKEND_CONTROLLER_PREFIX` - Default prefix for the backend controllers' route identifier
- `B13_MAKE_COMMAND_DIR` - Default directory for commands
- `B13_MAKE_COMMAND_NAME_PREFIX` - Default prefix for commands
- `B13_MAKE_EVENT_LISTENER_DIR` - Default directory for event listeners
- `B13_MAKE_EVENT_LISTENER_IDENTIFIER_PREFIX` - Default identifier prefix for event listeners
- `B13_MAKE_EXTENSION_DIR` - Default directory for extensions
- `B13_MAKE_MIDDLEWARE_DIR` - Default directory for middlewares
- `B13_MAKE_MIDDLEWARE_IDENTIFIER_PREFIX` - Default identifier prefix for middlewares
- `B13_MAKE_MIDDLEWARE_TYPE` - Default context type for middlewares

All component related commands require an extension name, for which the
component should be created. This can also be set as first argument or
globally with the `B13_MAKE_EXTENSION_KEY` environment variable.

## Commands

Following commands are available

- `make:backendcontroller` - Create a new backend controller
- `make:command` - Create a new command
- `make:eventlistener` - Create a new event listener
- `make:extension` - Create a new extension
- `make:middleware` - Create a new middleware

## Credits

This extension was created by Oliver Bartsch in 2021 for [b13 GmbH, Stuttgart](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you)
that help us deliver value in client projects. As part of the way we work,
we focus on testing and best practices ensuring long-term performance,
reliability, and results in all our code.
