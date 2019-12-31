<template>
  <div class="p-card p-entry" :class="{'compact': compact}" @click="compact = !compact">
    
    <div class="p-card-body" v-if="entry.state == 'unfurling'">
      <div class="main">
        <div class="site">
          <div class="loading-sitename"></div>
        </div>
        <span class="link">
          <div class="loading-link"></div>
        </span>
      </div>
      <div class="actions">
        <i class="podlove-icon-spinner rotate"></i>
      </div>
    </div>

    <div class="p-card-body" v-else-if="entry.url">
      <div class="main" v-if="!edit">
        <div class="p-entry-container">
          <div class="p-entry-favicon">
            <img v-if="entry.icon" :src="icon" width="16" height="16">
            <div v-else class="default-icon"></div>
          </div>
          <div class="p-entry-content">
            <div class="p-entry-site">{{ entry.site_name }}</div>
            <span class="link p-entry-title-url">
              <a :href="entry.url" target="_blank">{{ entry.title }}</a>
            </span>
            <br>
            <span class="p-entry-url-url">
              <a :href="entry.url" target="_blank">{{ entry.url }}</a>
            </span>
            <div class="p-entry-description" v-if="entry.description">{{ entry.description }}</div>
          </div>
          <div class="p-entry-actions">
            <span class="retry-btn" title="edit" v-if="!edit" @click.prevent="edit = true">
              <icon-edit></icon-edit>
            </span>
            <span class="retry-btn" title="refresh" v-if="!edit" @click.prevent="unfurl()">
              <icon-refresh></icon-refresh>
            </span>            
            <div class="drag-handle">
              <icon-menu></icon-menu>
            </div>
          </div>
        </div>
      </div>
      <div class="main" v-else>
        
        <div class="edit-section">
          <label>
          <span>URL</span>
            <input type="text" placeholder="URL" name="url" v-model="entry.url"/>
          </label>
        </div>

        <div class="edit-section">
          <label>
            <span>Title</span>
            <input type="text" placeholder="Title" name="title" v-model="entry.title"/>
          </label>
        </div>

        <div class="edit-section">
          <label>
            <span>Description</span>
            <textarea rows="3" placeholder="Description" name="description" v-model="entry.description"/>
          </label>
        </div>

        <div class="edit-section edit-actions">
          <div>
            <a href="#" class="button button-primary" @click.prevent="save()">Save Changes</a>
            <a href="#" class="button" @click.prevent="edit = false">Cancel</a>
          </div>
          <div>
            <a href="#" class="delete-btn destructive" @click.prevent="deleteEntry()">Delete Entry</a>
          </div>
        </div>        

      </div>
    </div>

    <div class="p-card-body failed" v-else-if="entry.state == 'failed'">
      <div class="main">Unable to access URL: {{ entry.original_url }}</div>
      <div class="supplementary" style="display: flex; margin-left: 12px">
        <div class="actions">
          <a href="#" class="retry-btn" @click.prevent="unfurl()">retry?</a>
          <a href="#" class="delete-btn destructive" @click.prevent="deleteEntry()">delete</a>
        </div>
        <div style="margin-left: 12px">
          <div class="drag-handle">
            <icon-menu></icon-menu>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import CheveronDown from "./icons/CheveronDown";
import CheveronUp from "./icons/CheveronUp";
import Menu from "./icons/Menu";
import Refresh from "./icons/Refresh";
import Edit from "./icons/Edit";

export default {
  props: ["entry"],
  data() {
    return {
      compact: true,
      edit: false
    };
  },
  components: {
    "icon-cheveron-down": CheveronDown,
    "icon-cheveron-up": CheveronUp,
    "icon-menu": Menu,
    "icon-refresh": Refresh,
    "icon-edit": Edit
  },
  computed: {
    icon: function() {
      if (this.entry.icon[0] == "/") {
        return this.entry.site_url + this.entry.icon;
      } else {
        return this.entry.icon;
      }
    }
  },
  methods: {
    unfurl: function() {
      this.entry.state = "unfurling";

      jQuery
        .post(
          podlove_vue.rest_url +
            "podlove/v1/shownotes/" +
            this.entry.id +
            "/unfurl"
        )
        .done(result => {
          this.$emit("update:entry", result);
        })
        .fail(({ responseJSON }) => {
          this.entry.state = "failed";
          console.error("could not unfurl entry:", responseJSON.message);
        });
    },
    save: function() {
      this.edit = false;

      this.$emit("update:entry", this.entry);

      jQuery
        .post(podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id, {
          url: this.entry.url,
          title: this.entry.title,
          description: this.entry.description
        })
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
    deleteEntry: function() {
      this.$emit("delete:entry", this.entry);

      jQuery
        .ajax({
          url: podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          method: "DELETE",
          dataType: "json"
        })
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    }
  },
  mounted: function() {
    if (!this.entry.state) {
      this.unfurl();
    }
  }
};
</script>

<style>
.compact .link {
  max-height: 18px;
}

.compact .description {
  display: none;
}

.p-entry:hover {
  background: hsl(197, 90%, 97%);
}

/* .link {
  display: flex;
  align-items: flex-start;
  overflow: hidden;
} */

.link a {
  color: #333;
}

.primary {
  fill: #a5b3bb;
}
.secondary {
  fill: #0d2b3e;
}
.disclose {
  cursor: pointer;
  display: inline-block;
  width: 22px;
  height: 22px;
}
.drag-handle {
  display: inline-block;
  width: 16px;
  height: 16px;
  cursor: grab;
}
.drag-handle:active {
  cursor: grabbing;
}

.drag-handle .secondary {
  fill: #999;
}

.actions {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  min-width: 75px;
}
.actions a {
  display: block;
  /* box-shadow: 0 0 2px rgba(0, 0, 0, 0.1), 1px 1px 1px rgba(0, 0, 0, 0.2); */
  font-size: 11px;
  line-height: 11px;
  /* padding: 2px 4px; */
  color: #666;
}

a.destructive {
  color: #ef7885;
}

.p-entry-favicon div,
.p-entry-favicon img {
  box-shadow: 1px 1px 1px rgba(0, 0, 0, 0.2);
}

.supplementary {
  display: flex;
  align-items: center;
  margin-left: 10px;
  height: 40px;
}

.supplementary {
  visibility: hidden;
}

.p-card:hover .supplementary,
.supplementary.edit {
  visibility: visible;
}

.main {
  width: 100%;
}

.edit-section {
  margin-top: 12px;
}

.edit-section:first {
  margin-top: 0px;
}

.edit-section label span {
  display: block;
  margin-bottom: 4px;
}

.edit-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.retry-btn {
  display: inline-block;
  width: 16px;
  height: 16px;
  color: #999;
  cursor: pointer;
  margin-right: 6px;
}

.p-entry-container {
  display: flex;
  align-items: start;
}

.p-entry-content {
  flex-grow: 2;
  padding-right: 12px;
}

.p-entry-actions {
  min-width: 68px;
}

.p-entry-favicon {
  padding-right: 12px;
}

.p-entry-url-url a {
  color: #999;
}

.p-entry-site {
  font-size: 11px;
  margin-bottom: 9px;
}

.p-entry-description {
  font-size: 11px;
  margin-top: 9px;
}

.p-entry-url,
.p-entry-site,
.p-entry-description {
  color: #999;
}
</style>
