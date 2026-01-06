# Custom Record Auto-Numbering

Luke Stevens, Murdoch Children's Research Institute https://www.mcri.edu.au

[https://github.com/lsgs/redcap-record-autonumbering/](https://github.com/lsgs/redcap-record-autonumbering/)

This module provides various alternative record auto-numbering schemas for REDCap project records, e.g. date/time-stamp, or padding/incrementing within a site/DAG utilising part of the DAG name rather than the built-in DAG ID. Note: custom auto-numbering works only during data entry; not for public surveys or with API/CSV data imports.

## Motivation

Normal REDCap behavior dictates that having auto-numbering enabled in a project makes the first record created have a record ID of 1, the second record created have an ID of 2, the third record created have a record ID of 3, and so on. If the project is using DAGs (Data Entry Groups), having auto-numbering enabled will automatically have its record name prepended with the Group ID (DAG ID) number and a dash/hyphen. For example, the first three records IDs created for DAG ID 98 will get automatically named 98-1, 98-2, and 98-3.

This module introduces a range of additional options, such as having the first record ID created start at 1000 or 001 instead of 1, prepend all record IDs with a prefix (ABC-1, ABC-2, ABC-3, etc.), or having the DAG name appear in the record ID (UF-1, UF-2, UF-3) instead of the DAG ID (98-1, 98-2, 98-3, etc.) when utilizing DAGs in a project.

## Limitations

-   The custom record auto-numbering system only applies to records *created* by a logged-in user, i.e. not via a public survey or API/CSV data import.

-   The module works best when enabled and configured *before* the first record has been created in the project, especially when using options 1, 2 or 3 listed below.

-   This module will *not* automatically convert existing records to the project's newly defined record ID schema; it only applies to *new* records that are created, by a logged-in user, once the module is enabled.

-   If the project is utilising randomisation then the "Randomize" button is hidden until the record is saved and the appropriate auto-numbered record id is generated.

## Project Configuration

1. **Integer increment from a specified start value**: This option allows users to specify the first numerical record ID. Subsequent record IDs will increment from this project-wide (even for users in a DAG).
    - This is useful when you want your first record ID to start with any integer besides 1, including padding the 1 to become 001.

1. **Padded integer increment with prefix**: This option allows users to specify a prefix to the project's record IDs and configure the padding length, prepending the ID with zeros to achieve the desired length. Subsequent record IDs will increment from this project-wide (even for users in a DAG).
  - This is useful when you want you need to create study-specific record IDs, such as ABC-001, ABC-002, ABC-003, etc.

1. **Increment within DAG using part of the DAG name**: This option lets users create a DAG-specific prefix to the record IDs in the project, when DAGs are used in a project. This option gives users the ability to use 1-5 characters from the beginning or the end of the DAG as part of the record ID.
  - This is useful when you need to create DAG-specific record IDs, such as UFL-1, UFL-2, UW-1, UW-2, USF-1, USF-2, etc.
  - _Useful tip_: if you want the prefixed DAG ID to be "UFL" you can name the DAG "University of Florida UFL" and specify you want the last 3 letters of the DAG name to be used in the prefix.

1. **Date/time in selected format**: This option will create a record ID based upon the date and time a record was created.
    - _Note_: Since dates are PHI, do not use this option if all data is supposed to be de-identified.

1. **Unix timestamp (16 digits)**: This option will create a record ID based upon a Unix timestamp. A Unix timestamp is the number of seconds since January 1st, 1970 (UTC).
  - See: <https://www.unixtimestamp.com/> for more information about Unix timestamps.

1. **A project-specific custom auto-numbering schema**: The module design supports the addition of novel auto-numbering schemes via custom code. This is an advanced feature for module developers.

For a more detailed explanation, see the [Custom Record Auto-numbering User Guide](https://www.ctsi.ufl.edu/wordpress/files/2021/04/Custom-Record-Auto-numbering-External-Module-User-Guide.pdf) created by Taryn Stoffs.
