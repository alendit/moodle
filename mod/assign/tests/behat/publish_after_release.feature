@mod @mod_assign
Feature: In an assignment, techer can submit grades and release them using workflow
  To easily mark and release multiple assignment while using the workflow the
  As a teacher
  I need the grades to be released while setting the status using batch interface
  

  @javascript @_file_upload
  Scenario: Submit a text online and edit the submission
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2  | Student | 2  | student2@example.com  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2  | C1     | student        |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
      | assignfeedback_comments_enabled | 1 |
      | assignfeedback_file_enabled | 1 |
      | assignfeedback_comments_commentinline | 1 |
      | Students submit in groups | Yes |
      | Use marking workflow | Yes  |
    # Set a mark and the marking workflow to 'Released'.
    And I follow "Test assignment name"
    And I follow "View all submissions"
    And I should see "Not marked" in the "Student 1" "table_row"
    # Mark submission
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grade out of 100" to "50"
    And I set the field "Feedback comments" to "Great job! Lol, not really."
    And I press "Save changes"
    And I press "Ok"
    And I click on "Edit settings" "link"
    And I follow "Test assignment name"
    And I follow "View all submissions"
    # Release the grade
    And I set the field "selectall" to "1"
    And I set the field "id_operation" to "Set marking workflow state"
    And I click on "Go" "button" confirming the dialogue
    And I set the field "id_markingworkflowstate" to "Released"
    And I press "Save changes"
    # Check the grad
    And I set the field "Grading action" to "View gradebook"
    And I should see "50.00" in the "Student 1" "table_row"
    And I log out

