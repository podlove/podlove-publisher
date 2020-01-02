<template>
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
        <icon-link class="p-entry-icon" style="margin-bottom: 9px"></icon-link>
        <img v-if="entry.icon" :src="icon" width="16" height="16">
        <div v-else class="default-icon">
          <icon-image class="p-entry-icon"></icon-image>
        </div>
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
        <span class="retry-btn" title="refresh" v-if="!edit" @click.prevent="unfurl()">
          <icon-refresh></icon-refresh>
        </span>            
        <span class="retry-btn" title="edit" v-if="!edit" @click.prevent="edit = true">
          <icon-edit></icon-edit>
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
        <input type="text" placeholder="URL" name="url" 
            @keydown.enter.prevent="save()" 
            @keydown.esc="edit = false"
            v-model="entry.url"/>
      </label>
    </div>
    <div class="edit-section">
      <label>
        <span>Title</span>
        <input type="text" placeholder="Title" name="title" 
            @keydown.enter.prevent="save()" 
            @keydown.esc="edit = false"
            v-model="entry.title"/>
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
</template>

<script>
import CheveronDown from "../icons/CheveronDown";
import CheveronUp from "../icons/CheveronUp";
import Menu from "../icons/Menu";
import Refresh from "../icons/Refresh";
import Edit from "../icons/Edit";
import Image from "../icons/Image";
import Link from "../icons/Link";
import Type from "../icons/Type";

export default {
  props: ["entry"],
  data() {
    return {
      edit: false
    };
  },
  components: {
    "icon-cheveron-down": CheveronDown,
    "icon-cheveron-up": CheveronUp,
    "icon-menu": Menu,
    "icon-refresh": Refresh,
    "icon-edit": Edit,
    "icon-image": Image,
    "icon-type": Type,
    "icon-link": Link
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
          this.$parent.$emit("update:entry", result);
        })
        .fail(({ responseJSON }) => {
          this.entry.state = "failed";
          console.error("could not unfurl entry:", responseJSON.message);
        });
    },
    save: function() {
      this.edit = false;

      this.$parent.$emit("update:entry", this.entry);

      let payload = {};

      payload.url = this.entry.url;
      payload.title = this.entry.title;
      payload.description = this.entry.description;

      jQuery
        .post(
          podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          payload
        )
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
    deleteEntry: function() {
      this.$parent.$emit("delete:entry", this.entry);

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
