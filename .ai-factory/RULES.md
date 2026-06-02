# Project Rules

> Short, actionable rules and conventions for this project. Loaded automatically by /aif-implement.

## Rules

- Work in low-token batch mode for long-running commands: do not stream progress, redirect all stdout/stderr to a log file, wait for completion, check exit code, read only final summary/config/result files on success, and read only the last 100-200 log lines on failure.
