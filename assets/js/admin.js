document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.teamops-form-card select[multiple]').forEach(function (field) {
    field.style.minHeight = '140px';
  });

  var projectField = document.getElementById('teamops-task-project');
  var assigneeField = document.getElementById('teamops-task-assignee');
  var milestoneField = document.getElementById('teamops-task-milestone');

  if (!projectField || !assigneeField) {
    return;
  }

  var optionCache = Array.from(assigneeField.options).map(function (option) {
    return {
      value: option.value,
      text: option.text,
      selected: option.selected
    };
  });

  var syncAssignees = function () {
    var selectedProject = projectField.options[projectField.selectedIndex];
    var memberIds = selectedProject ? (selectedProject.dataset.memberIds || '') : '';
    var allowedIds = memberIds
      .split(',')
      .map(function (id) {
        return id.trim();
      })
      .filter(Boolean);
    var selectedValue = assigneeField.value;

    assigneeField.innerHTML = '';

    optionCache.forEach(function (option) {
      if (allowedIds.length && !allowedIds.includes(option.value)) {
        return;
      }

      var renderedOption = document.createElement('option');
      renderedOption.value = option.value;
      renderedOption.textContent = option.text;
      renderedOption.selected = option.value === selectedValue || (!selectedValue && option.selected);
      assigneeField.appendChild(renderedOption);
    });

    if (!assigneeField.value && assigneeField.options.length) {
      assigneeField.options[0].selected = true;
    }
  };

  var syncMilestones = function () {
    if (!milestoneField) {
      return;
    }

    var selectedProjectId = projectField.value;
    var selectedMilestoneId = milestoneField.value;

    Array.from(milestoneField.options).forEach(function (option, index) {
      if (index === 0) {
        option.hidden = false;
        return;
      }

      var optionProjectId = option.dataset.projectId || '';
      option.hidden = optionProjectId !== selectedProjectId;
    });

    if (
      selectedMilestoneId &&
      milestoneField.selectedIndex > 0 &&
      milestoneField.options[milestoneField.selectedIndex].hidden
    ) {
      milestoneField.value = '0';
    }
  };

  projectField.addEventListener('change', function () {
    syncAssignees();
    syncMilestones();
  });
  syncAssignees();
  syncMilestones();
});
