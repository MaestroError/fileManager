# fileManager

Easy to use, chainable PHP fileManager class with UNIX-like methods

---

Refactoring plan, structure and features:

- Keeps state of file explorer in object
- Hooks file's read/write/delete by file extension
- Folders as property and via method:
  - `$fileManager->testFolder->open();`
  - `$fileManager->open("Test folder");`
- Main objects: Directory & File
  - Directory:
    - Name +
    - Path +
    - Files +
    - Directories +
    - Tree +
    - Size +
    - Date +
  - File:
    - Name +
    - Path +
    - Content +
    - Size +
    - Date +
- Entry Class: FileManager
  - Current directory
  - Folders array (For Safe property access) as trait for this and Directory class
  - Main actions (API)

### To Do

Needs Release

- Solve todo comments
- Add description comments for each method
- Seo, Examples and Documentation
- Add tests for main methods
