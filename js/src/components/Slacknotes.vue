<template>
  <div>
    <div class="p-card" v-if="!module_active">
      <div class="p-card-header">
        <strong>Slacknotes module inactive</strong>
      </div>
      <div
        class="p-card-body"
      >You need to activate and setup the Slacknotes Publisher Module before you can use this import function.</div>
    </div>
    <div class="p-card" v-if="!module_token_set">
      <div class="p-card-header">
        <strong>Slack OAuth Access Token missing</strong>
      </div>
      <div
        class="p-card-body"
      >You need to set the Slack OAuth Access Token in the Slacknotes Publisher Module settings before you can use this import function.</div>
    </div>
    <div class="p-card" v-if="channelsLoading && module_active && module_token_set">
      <div class="p-card-header">
        <strong>Loading channels ...</strong>
      </div>
      <div class="p-card-body" style="display: flex; justify-content: center;">
        <i class="podlove-icon-spinner rotate" style="margin: 35px 0;"></i>
      </div>
    </div>
    <div class="p-card" v-else-if="module_active && module_token_set">
      <div class="p-card-header">
        <strong>Select Slack Channel</strong>
      </div>
      <div class="p-card-body slacknotes-toolbar">
        <div style="display: flex">
          <select v-model="currentChannel" @change="fetchLinks">
            <option value="0">Select Slack Channel</option>
            <option
              v-for="channel in channels"
              :value="channel.id"
              :key="channel.id"
            >#{{ channel.name }}</option>
          </select>

          <v2-datepicker-range
            v-model="dates"
            @change="onDatepickerChange"
            lang="en"
            :default-value="defaultRange"
            :picker-options="options"
          ></v2-datepicker-range>
        </div>

        <div class="button-group">
          <button
            @click="setOrder('desc')"
            type="button"
            :class="{'button-active': order == 'desc'}"
            class="button"
          >Newest First</button>
          <button
            @click="setOrder('asc')"
            type="button"
            :class="{'button-active': order == 'asc'}"
            class="button"
          >Oldest First</button>
        </div>
      </div>
    </div>

    <div class="p-card slack-links-empty" v-if="!channelsLoading && !linksReady">
      <div class="p-card-header">
        <strong>Loading links ...</strong>
      </div>
      <div class="p-card-body" style="display: flex; justify-content: center;">
        <i class="podlove-icon-spinner rotate" style="margin: 35px 0;"></i>
      </div>
    </div>

    <div v-if="linksReady && links.length == 0" class="p-card slack-links-empty">
      <div class="p-card-body">There are no links for the selected channel.</div>
    </div>

    <div class="slack-links" v-show="linksReady">
      <div
        class="p-card slack-link"
        v-bind:class="{ excluded: link.excluded }"
        v-for="link in sortedLinks"
        :key="link.id"
      >
        <div class="p-card-body" style="display: flex; justify-content: space-between">
          <div class="slack-link-select">
            <input type="checkbox" :checked="!link.excluded" @change="toggleExclusion(link)">
          </div>
          <div class="slack-link-content" style="flex-grow: 10">
            <div class="slack-card-headline">
              <span class="link-title" v-if="link.title">{{ link.title }}</span>
              <span v-else>
                <span class="bar-loading" v-if="isFetching(link)"></span>
                <a
                  href="#"
                  class="unknown-title"
                  @click.prevent="fetchLinkTitle(link)"
                  v-if="!isFetching(link)"
                >try to fetch title from website</a>
              </span>
              <span class="link-source" v-if="link.source">{{ link.source }}</span>
            </div>
            <span class="link-url" v-if="link.link">
              <a :href="link.link" target="_blank">{{ link.link }}</a>
            </span>
          </div>
          <div class="slack-link-date">
            {{ linkDate(link) }}
            <br>
            {{ linkTime(link) }}
          </div>
        </div>
      </div>
    </div>

    <div class="output-container p-card" v-if="mode != 'import'" v-show="linksReady">
      <div class="output-header p-card-header">
        <strong>Shownotes HTML</strong>
      </div>
      <pre class="output p-card-body" id="clipboard-target">{{ renderedHTML }}</pre>
      <div class="output-footer p-card-header" style="vertical-align: baseline; line-height: 28px;">
        <span class="button clipboard-btn" data-clipboard-target="#clipboard-target">Copy HTML</span>
        <transition name="fade">
          <span
            v-show="showCopySuccess"
            class="copy-success"
            style="padding-left: 8px; color: #2c6e36; text-shadow: 1px 1px 1px white; display: none;"
          >Shownotes copied to clipboard</span>
        </transition>
      </div>
    </div>

    <div
      class="output-footer p-card-header"
      style="margin-bottom: 12px;"
      v-show="mode == 'import' && linksReady"
    >
      <span
        class="button button-primary"
        @click="importToEpisode()"
      >Import {{ this.entriesForImport.length }} Entries</span>
    </div>
  </div>
</template>

<script>
const $ = jQuery;

import ClipboardJS from "clipboard";

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
  props: ["mode"],
  data() {
    return {
      channels: [],
      channelsLoading: true,
      links: [],
      linksLoading: false,
      initializing: true,
      currentChannel: null,
      showCopySuccess: false,
      module_active: true,
      module_token_set: true,
      dates: [],
      fetching: [],
      order: "desc",
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
    },
    apiUrlMessages: function() {
      return (
        podlove_vue.rest_url +
        "podlove/v1/slacknotes/" +
        this.currentChannel +
        "/messages"
      );
    },
    renderedHTML: function() {
      let html = "<ul>\n";

      for (let j = 0; j < this.sortedLinks.length; j++) {
        const link = this.links[j];
        const title = link.title ? link.title : link.link;

        if (!link.excluded) {
          html +=
            '    <li><a href="' +
            link.link +
            '">' +
            title +
            "</a> (" +
            link.source +
            ")</li>\n";
        }
      }

      html += "</ul>\n";

      return html;
    },
    entriesForImport: function() {
      return this.sortedLinks
        .filter(l => !l.excluded)
        .map(l => {
          return { url: l.link, data: l };
        });
    },
    linksReady: function() {
      return !this.initializing && this.links != [] && !this.linksLoading;
    },
    sortedLinks: function() {
      return this.links.sort((a, b) => {
        if (this.order == "asc") {
          return a.unix_date - b.unix_date;
        } else {
          return b.unix_date - a.unix_date;
        }
      });
    }
  },

  methods: {
    importToEpisode: function() {
      let entries = this.entriesForImport;
      this.$emit("import:entries", entries);
    },
    onDatepickerChange: function(range) {
      if (range.length == 2) {
        this.dates = [startOfDay(range[0]), endOfDay(range[1])];
      }

      this.fetchLinks();
    },
    setOrder: function(order) {
      this.order = order;

      if (localStorage) {
        localStorage.setItem("podlove-slacknotes-order", this.order);
      }
    },
    fetchLinks: function() {
      if (this.currentChannel) {
        this.linksLoading = true;

        if (localStorage) {
          localStorage.setItem(
            "podlove-slacknotes-channel",
            this.currentChannel
          );
        }

        let date_from = 0;
        let date_to = 0;

        if (this.dates.length === 2) {
          date_from = this.dates[0].getTime() / 1000;
          date_to = this.dates[1].getTime() / 1000;
        }

        $.ajax({
          url: this.apiUrlMessages,
          data: {
            date_from: date_from,
            date_to: date_to
          }
        })
          .done(data => {
            const reduceMessagesToLinks = function(links, message) {
              for (let i = 0; i < message.links.length; i++) {
                const unix_date =
                  parseInt(message.raw_slack_message.ts, 10) * 1000;
                const datetime = new Date(unix_date);
                const link = message.links[i];

                link.unix_date = unix_date;
                link.datetime = datetime;
                link.id = unix_date + link.link;

                links.push(link);
              }

              return links;
            };

            const addExcludedField = function(link) {
              link.excluded = false;
              return link;
            };

            const links = data
              .reduce(reduceMessagesToLinks, [])
              .map(addExcludedField);

            this.links = links;
            this.linksLoading = false;
            this.initializing = false;

            // fetch missing titles
            window.setTimeout(() => {
              let list = document.getElementsByClassName("unknown-title");

              for (let i = 0; i < list.length; i++) {
                const element = list[i];
                element.click();
              }
            }, 200);
          })
          .fail(e => console.error("Slacknotes failed fetching messages", e));
      } else {
        this.messages = [];
      }
    },
    isFetching: function(link) {
      const url = link.link;
      return this.fetching.includes(url);
    },
    fetchLinkTitle: function(link) {
      const url = link.link;

      this.fetching.push(url);

      $.ajax(
        podlove_vue.rest_url +
          "podlove/v1/slacknotes/resolve_url?url=" +
          encodeURIComponent(url)
      ).done(data => {
        if (data.title) {
          link.title = data.title;
        }

        if (data.url) {
          link.link = data.url;
        }

        // delete from fetching
        var index = this.fetching.indexOf(url);
        if (index !== -1) this.fetching.splice(index, 1);
      });
    },
    toggleExclusion: function(link) {
      link.excluded = !link.excluded;
    },
    linkDate: function(link) {
      const date = link.datetime;
      const y = date.getFullYear();
      const m = date.getMonth() + 1;
      const d = date.getUTCDate();

      return d + "." + m + "." + y;
    },
    linkTime: function(link) {
      const date = link.datetime;
      const h = date.getHours();
      let m = date.getMinutes();

      if (m < 10) {
        m = "0" + m;
      }

      return h + ":" + m;
    }
  },

  mounted() {
    $.when($.ajax(podlove_vue.rest_url + "podlove/v1/slacknotes/channels"))
      .done(channelData => {
        this.channels = channelData;
        this.channelsLoading = false;

        let savedChannel = null;
        let savedOrder = null;

        if (localStorage) {
          savedChannel = localStorage.getItem("podlove-slacknotes-channel");
          savedOrder = localStorage.getItem("podlove-slacknotes-order");
        }

        if (savedChannel) {
          this.currentChannel = savedChannel;
        }

        if (savedOrder) {
          this.order = savedOrder;
        }

        this.fetchLinks();
      })
      .fail(e => {
        console.error("Slacknotes failed fetching channels", e);

        if (e.responseJSON.code == "rest_no_route") {
          this.module_active = false;
        }
        if (e.responseJSON.code == "podlove_slacknotes_no_token") {
          this.module_token_set = false;
        }
      });

    let clip = new ClipboardJS(".clipboard-btn");
    clip.on("success", e => {
      this.showCopySuccess = true;
      window.setTimeout(() => {
        this.showCopySuccess = false;
      }, 3000);
    });
  }
};
</script>

<style>
.v2-picker-panel-wrap {
  z-index: 150000 !important;
}
</style>

<style scoped>
.slack-links,
.slack-links-empty {
  margin-top: 2rem;
}

.slack-link {
  margin-bottom: 6px;
}

.slack-link.excluded {
  opacity: 0.5;
}

.p-card {
  background: white;
  border: 1px solid #ddd;
}

.p-card-body {
  padding: 12px;
}

.slack-card-headline {
  margin-bottom: 6px;
}

.p-card-header,
.p-card-footer {
  background: #e9e9e9;
  padding: 12px;
}

.slack-link-select {
  margin-right: 6px;
}

.link-title {
  font-weight: bold;
  font-size: 15px;
}
.link-source {
  font-style: italic;
}
.link-url a {
  text-decoration: none;
}

.output-container {
  margin-top: 2rem;
}

.output {
  font-family: monospace;
  line-height: 22px;
  margin: 0;
  overflow-x: auto;
}

.slack-link-date {
  padding-left: 6px;
  text-align: right;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}

.bar-loading {
  animation: colorchange ease-in 2s running infinite;
  display: inline-block;
  background: #999;
  width: 250px;
  height: 12px;
  border-radius: 3px;
}

@keyframes colorchange {
  0% {
    background: #ccc;
  }
  50% {
    background: #ddd;
  }
  100% {
    background: #ccc;
  }
}

.button-group .button.button-active {
  background: #eee;
  border-color: #999;
  box-shadow: inset 0 2px 5px -3px rgba(0, 0, 0, 0.5);
  transform: translateY(1px);
}

.slacknotes-toolbar {
  display: flex;
  justify-content: space-between;
}
</style>
