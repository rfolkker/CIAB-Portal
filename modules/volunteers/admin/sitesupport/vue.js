/* jshint esversion: 6 */
/* globals Vue, adminMode */

import lookupUser from '../../../../sitesupport/vue/lookupuser.js'
import hourTable from '../../sitesupport/hourTable.js'
import editHours from './editHours.js'
import editPrize from './editPrize.js'
import departmentDropdown from '../../../../../sitesupport/vue/departmentDropdown.js'
import giftTable from './giftTable.js'

var adminHourTable = {
  extends: hourTable,
  methods: {
    clicked(record) {
      if (adminMode && this.user) {
        this.$parent.$refs.edhrs.show(record);
      }
    }
  }
}

var app = Vue.createApp({
  data() {
    return {
      totalHours: 0,
      totalSpentHours: 0
    }
  },
  methods: {
    handleHourChange(totalHours, totalSpentHours) {
      this.totalHours = parseFloat(totalHours);
      this.totalSpentHours = parseFloat(totalSpentHours);
    }
  }
});

app.component('lookup-user', lookupUser);
app.component('volunteer-hour-table', adminHourTable);
app.component('edit-hours', editHours);
app.component('edit-prize', editPrize);
app.component('department-dropdown', departmentDropdown);
app.component('gift-table', giftTable);
app.mount('#main_content');

export default app;
