<template>
  <div class="podlove-analytics-datepicker">
    <v2-datepicker-range
      @change="onChange"
      lang="en"
      format="YYYY-MM-DD"
      v-model="range"
      :default-value="defaultRange"
      :picker-options="options"
    ></v2-datepicker-range>
  </div>
</template>

<script>
const TIME_DAY = 3600 * 1000 * 24;

const startOfDay = date => {
  date.setHours(0, 0, 0, 0);
  return date;
};

const endOfDay = date => {
  date.setHours(23, 59, 59, 999);
  return date;
};

export default {
  data: function() {
    return {
      range: 0,
      options: {
        disabledDate(time) {
          return time.getTime() > Date.now();
        },
        shortcuts: [
          {
            text: "Today",
            onClick(picker) {
              const end = startOfDay(new Date());
              const start = endOfDay(new Date());

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Yesterday",
            onClick(picker) {
              const end = new Date();
              const start = new Date();

              end.setTime(end.getTime() - TIME_DAY);
              start.setTime(start.getTime() - TIME_DAY);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Last week",
            onClick(picker) {
              const end = new Date();
              const start = new Date();
              start.setTime(start.getTime() - TIME_DAY * 7);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Last month",
            onClick(picker) {
              const end = new Date();
              const start = new Date();
              start.setTime(start.getTime() - TIME_DAY * 30);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Last 3 months",
            onClick(picker) {
              const end = new Date();
              const start = new Date();
              start.setTime(start.getTime() - TIME_DAY * 90);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Last Year",
            onClick(picker) {
              const end = new Date();
              const start = new Date();
              start.setTime(start.getTime() - TIME_DAY * 365);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          },
          {
            text: "Last 10 Years",
            onClick(picker) {
              const end = new Date();
              const start = new Date();
              start.setTime(start.getTime() - TIME_DAY * 365 * 10);

              picker.$emit("pick", [startOfDay(start), endOfDay(end)]);
            }
          }
        ]
      }
    };
  },
  computed: {
    defaultRange() {
      const end = new Date();
      const start = new Date();
      start.setTime(start.getTime() - TIME_DAY * 30);

      return [startOfDay(start), endOfDay(end)];
    }
  },
  methods: {
    broadcastRange: function(range) {
      this.$parent.$emit("setChartRange", range);
    },
    onChange: function(range) {
      this.broadcastRange(range);
    }
  },
  mounted() {
    this.$nextTick(function() {
      this.broadcastRange(this.defaultRange);
    });

    // because chart stuff is rendered 'on load'
    window.onload = () => {
        // apparently too early, so give it a little time
        window.setTimeout(
            () => { this.broadcastRange(this.defaultRange); },
            250
        )
    };
  }
};
</script>
