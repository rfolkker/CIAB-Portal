/* globals Vue, apiRequest */
import { extractDepartmentStaff, sortStaffByPosition } from '../department-staff-parser.js';

const PROPS = {
  division: Object
};

const TEMPLATE = `
  <div class="UI-container UI-padding UI-border">
    <div class="UI-table UI-table-heading" :id="htmlTagFriendlyName(division).value">
      <div class="UI-table-row event-color-secondary">
        <div class="UI-center UI-table-cell-no-border">{{divisionName(division).value}}</div>
        <div class="UI-center UI-table-cell-no-border">
          <staff-section-nav :id="htmlTagFriendlyName(division).value + '_nav'"></staff-section-nav>
        </div>
        <div class="UI-table-cell-no-border">
          <template v-for="email in division.email">{{email}}<br/></template>
        </div> 
      </div>
    </div>
    <staff-department :department=division :division=division :divisionStaffMap=divisionStaffMap></staff-department>
    <div class="UI-container UI-padding">
      <template v-for="department in division.departments">
        <staff-department :department=department :division=division :divisionStaffMap=divisionStaffMap></staff-department>
      </template>
    </div>
  </div>
`;

const htmlTagFriendlyName = (division) => Vue.computed(() => {
  return division.name.replaceAll(' ', '_');
});

const divisionName = (division) => Vue.computed(() => {
  return division.specialDivision ? division.name : `${division.name} Division`;
});

const INITIAL_DATA = () => {
  return {
    divisionStaffMap: {},
    htmlTagFriendlyName,
    divisionName
  }
};

const fetchDivisionStaff = async(divisionId) => {
  const response = await apiRequest('GET', `department/${divisionId}/staff?subdepartments=1`);
  const divisionStaffData = JSON.parse(response.responseText);
  return extractDepartmentStaff(divisionStaffData.data);
}

const onMounted = async(componentInstance) => {
  const divisionStaffResult = await fetchDivisionStaff(componentInstance.division.id);

  for (const staff of divisionStaffResult) {
    const deptId = `${staff.deptId}`;
    if (componentInstance.divisionStaffMap[deptId] == null) {
      componentInstance.divisionStaffMap[deptId] = [];
    }

    componentInstance.divisionStaffMap[deptId].push(staff);
  }

  for (const key in componentInstance.divisionStaffMap) {
    componentInstance.divisionStaffMap[key].sort(sortStaffByPosition);
  }
}

const staffDivisionComponent = {
  props: PROPS,
  template: TEMPLATE,
  data: INITIAL_DATA,
  async mounted() {
    await onMounted(this);
  }
};

export default staffDivisionComponent;