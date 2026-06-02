Work in low-token batch mode.

Task:
In the Moonshine admin panel, there is a “Package Settings” section. Implement two tabs in this section:

1. Data
2. Log

Requirements:
- Add a tabbed interface inside Package Settings.
- The “Data” tab should contain the existing package settings/data content.
- The “Log” tab should display package-related journal/log records.
- The Log tab must show the journal data provided in the specification/design.
- Preserve the existing admin UI style and component patterns.
- Do not break existing package settings functionality.

Implementation rules:
- Inspect the current Package Settings implementation first.
- Reuse existing components, layouts, tables, filters, and API patterns where possible.
- Keep changes minimal and localized.
- Follow the project’s naming conventions and code style.
- If there is already a generic Tabs component, use it.
- If log/journal data requires an API call, integrate it using the existing data-fetching approach used in the admin panel.
- Add loading, empty, and error states for the Log tab if applicable.

For long-running commands:
- Do not monitor the process in real time.
- Do not use tee if output will enter the context.
- Redirect all stdout/stderr to a log file.
- Wait until the command finishes.
- Check the exit code.
- If successful, read only final summary/config/result files.
- If failed, read only the last 100–200 lines of the log.

Expected result:
The Package Settings section in Moonshine admin has two working tabs: “Data” and “Log”. The Data tab keeps the current functionality, and the Log tab displays the required journal/log information.