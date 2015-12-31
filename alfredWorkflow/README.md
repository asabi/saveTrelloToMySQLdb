# An Alfred workflow for suggested proejcts and cards to work on

The purpose of the workflow is to suggest any "neglected" cards and projects.

You will need to edit the script filter to include your mysql host and password.

Type: "ts" (stands for Trello Suggest), in Alfred, and the script will suggest things to work on.

##The script makes the following assumption:

You use labels to keep track of projects.

I use "P-<project name>" in the label name to specify it as a project (that's a variable in the script)
When a card belongs to a project, I add it the project label, so I can filter by project and see all of the associated cards
When a project is completed, I change the label to "CP-" which stands for completed project.
