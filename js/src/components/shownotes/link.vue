<template>
  <div class="p-card-body">
    <div class="main" v-if="!edit">
      <div class="p-entry-container">
        <div class="p-entry-favicon">
          <icon-link
            class="p-entry-icon"
            style="margin-bottom: 9px"
          ></icon-link>
          <img v-if="entry.icon" :src="icon" width="16" height="16" />
          <div v-else class="default-icon">
            <icon-image class="p-entry-icon"></icon-image>
          </div>
        </div>
        <div class="p-entry-content" v-if="entry.state != 'unfurling'">
          <div class="p-entry-site">{{ entry.site_name }}</div>

          <div style="display: flex; justify-content: space-between">
            <div>
              <span class="link p-entry-title-url">
                <a :href="entry.url" target="_blank">{{ entry.title }}</a>
              </span>
              <br />
              <span v-if="entry.affiliate_url" class="p-entry-url-url">
                <a :href="entry.affiliate_url" target="_blank">{{
                  decodeURI(entry.affiliate_url)
                }}</a>
                (Affiliate)
              </span>
              <span v-else class="p-entry-url-url">
                <a :href="entry.url" target="_blank">{{
                  decodeURI(entry.url)
                }}</a>
              </span>
              <div class="p-entry-description" v-if="isHidden">
                Hidden entries are excluded from public display.
              </div>
              <div class="p-entry-description" v-else-if="entry.description">
                {{ entry.description }}
              </div>
            </div>
            <div style="max-width: 200px" v-if="entry.image">
              <img :src="entry.image" alt="" />
            </div>
          </div>
        </div>
        <div class="p-entry-content" v-else>
          <div class="p-entry-site">
            <i class="podlove-icon-spinner rotate"></i>
          </div>
          <span class="link p-entry-title-url">
            <div class="loading-link"></div>
          </span>
        </div>
        <div class="p-entry-actions">
          <!-- <span class="retry-btn" title="refresh" v-if="!edit" @click.prevent="unfurl()">
          <icon-refresh></icon-refresh>
        </span>             -->
          <span
            class="retry-btn"
            title="edit"
            v-if="!edit"
            @click.prevent="edit = true"
          >
            <icon-edit></icon-edit>
          </span>
          <span
            class="retry-btn"
            title="unhide"
            v-if="isHidden"
            @click.prevent="toggleHide()"
          >
            <icon-eye-off></icon-eye-off>
          </span>
          <span
            class="retry-btn"
            title="hide"
            v-if="!isHidden"
            @click.prevent="toggleHide()"
          >
            <icon-eye></icon-eye>
          </span>
          <div class="drag-handle">
            <icon-menu></icon-menu>
          </div>
        </div>
      </div>
    </div>
    <div class="main" v-else>
      <div class="edit-section">
        <div v-if="entry.image">
          <img :src="entry.image" style="height: 100px" />
        </div>
      </div>

      <div class="edit-section">
        <label>
          <span>URL</span>
          <input
            type="text"
            placeholder="URL"
            name="url"
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.url"
          />
        </label>

        <suggestion
          v-if="
            entry.unfurl_data &&
            entry.unfurl_data.url &&
            entry.unfurl_data.url != entry.url
          "
          :title="entry.unfurl_data.url"
          @accept="entry.url = entry.unfurl_data.url"
        ></suggestion>
      </div>

      <div class="edit-section">
        <label>
          <span>Site Name</span>
          <input
            type="text"
            placeholder="Site Name"
            name="site_name"
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.site_name"
          />
        </label>

        <suggestion
          v-if="
            entry.unfurl_data &&
            entry.unfurl_data.site_name &&
            entry.unfurl_data.site_name != entry.site_name
          "
          :title="entry.unfurl_data.site_name"
          @accept="entry.site_name = entry.unfurl_data.site_name"
        ></suggestion>
      </div>

      <div class="edit-section">
        <label>
          <span>Title</span>
          <input
            type="text"
            placeholder="Title"
            name="title"
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.title"
          />
        </label>

        <suggestion
          v-if="
            entry.unfurl_data &&
            entry.unfurl_data.title &&
            entry.unfurl_data.title != entry.title
          "
          :title="entry.unfurl_data.title"
          @accept="entry.title = entry.unfurl_data.title"
        ></suggestion>
      </div>

      <div class="edit-section">
        <label>
          <span>Description</span>
          <textarea
            rows="3"
            placeholder="Description"
            name="description"
            v-model="entry.description"
          />
        </label>

        <suggestion
          v-if="
            entry.unfurl_data &&
            entry.unfurl_data.description &&
            entry.unfurl_data.description != entry.description
          "
          :title="entry.unfurl_data.description"
          @accept="entry.description = entry.unfurl_data.description"
        ></suggestion>
      </div>
      <div class="edit-section edit-actions">
        <div>
          <a href="#" class="button button-primary" @click.prevent="save()"
            >Save Changes</a
          >
          <a href="#" class="button" @click.prevent="unfurl()">Unfurl</a>
          <a href="#" class="button" @click.prevent="edit = false">Cancel</a>
        </div>
        <div>
          <a
            href="#"
            class="delete-btn destructive"
            @click.prevent="deleteEntry()"
            >Delete Entry</a
          >
        </div>
      </div>
      <div
        class="footer-separator"
        v-if="entry.state == 'unfurling' || entry.state == 'failed'"
      ></div>
      <div class="edit-section-footer" v-if="entry.state == 'unfurling'">
        <i class="podlove-icon-spinner rotate"></i> Unfurling
      </div>
      <div
        class="edit-section-footer p-card-body failed"
        v-else-if="entry.state == 'failed'"
      >
        <div class="main">
          <div class="unfurl-error-title">
            Unable to access URL:
            <a :href="entry.original_url" target="_blank">{{
              entry.original_url
            }}</a>
          </div>

          <div class="unfurl-error-message" v-if="error_message">
            {{ error_message }}
          </div>
          <div
            class="unfurl-error-location-trace"
            v-if="trace_locations && trace_locations.length > 0"
          >
            <strong>Location Trace:</strong>
            <ul>
              <li v-for="(location, index) in trace_locations" :key="location">
                {{ index }}: {{ location }}
              </li>
            </ul>
          </div>

          <div class="edit-section">
            <label>
              <span>Original URL</span>
              <input
                type="text"
                placeholder="Original URL"
                name="original_url"
                @keydown.enter.prevent="saveOriginalUrl()"
                @keydown.esc="edit = false"
                v-model="entry.original_url"
              />
            </label>
          </div>

          <div class="edit-section edit-actions">
            <div>
              <a href="#" class="button" @click.prevent="saveOriginalUrl()"
                >Save and Unfurl</a
              >
            </div>
          </div>
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
import Eye from "../icons/Eye";
import EyeOff from "../icons/EyeOff";

import suggestion from "./suggestion";

export default {
  props: ["entry"],
  data() {
    return {
      edit: false,
      error_message: "",
      trace_locations: [],
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
    "icon-link": Link,
    "icon-eye": Eye,
    "icon-eye-off": EyeOff,
    suggestion: suggestion,
  },
  computed: {
    icon: function () {
      if (this.entry.icon[0] == "/") {
        return this.entry.site_url + this.entry.icon;
      } else {
        return this.entry.icon;
      }
    },
    isHidden: function () {
      return this.entry.hidden === "1";
    },
  },
  methods: {
    unfurl: function () {
      this.entry.state = "unfurling";
      this.error_message = "";
      this.trace_locations = [];

      jQuery
        .post(
          podlove_vue.rest_url +
            "podlove/v1/shownotes/" +
            this.entry.id +
            "/unfurl"
        )
        .done((result) => {
          this.$parent.$emit("update:entry", result);
        })
        .fail(({ responseJSON }) => {
          if (!this.entry.url) {
            this.entry.url = this.entry.original_url;
          }

          this.entry.state = "failed";
          this.error_message =
            "[HTTP " +
            responseJSON.data.status +
            "] " +
            responseJSON.code +
            ": " +
            responseJSON.message;
          this.trace_locations = responseJSON.data.locations;
          console.error("could not unfurl entry:", responseJSON.message);
        });
    },
    save: function () {
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
        .done((result) => {})
        .fail(({ responseJSON }) => {
          console.error("could not save entry:", responseJSON.message);
        });
    },
    saveOriginalUrl: function () {
      this.$parent.$emit("update:entry", this.entry);

      let payload = {};

      payload.original_url = this.entry.original_url;
      payload.url = this.entry.url;
      payload.title = this.entry.title;
      payload.description = this.entry.description;

      jQuery
        .post(
          podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          payload
        )
        .done((result) => {
          this.unfurl();
        })
        .fail(({ responseJSON }) => {
          console.error("could not save entry:", responseJSON.message);
        });
    },
    toggleHide: function () {
      this.$parent.$emit("update:entry", this.entry);

      this.entry.hidden = this.isHidden ? "0" : "1";

      let payload = { hidden: this.entry.hidden };

      jQuery
        .post(
          podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          payload
        )
        .done((result) => {})
        .fail(({ responseJSON }) => {
          console.error("could not save entry:", responseJSON.message);
        });
    },
    deleteEntry: function () {
      this.$parent.$emit("delete:entry", this.entry);

      jQuery
        .ajax({
          url: podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          method: "DELETE",
          dataType: "json",
        })
        .done((result) => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
  },
  mounted: function () {
    if (!this.entry.state) {
      this.unfurl();
    }
  },
};
</script>

<style lang="css">
.unfurl-error-title,
.unfurl-error-message {
  /* margin-bottom: 9px; */
}

.unfurl-error-title {
  font-weight: bold;
  font-size: 13px;
}

.unfurl-error-message {
  color: #dc3232;
}

.unfurl-error-location-trace,
.unfurl-error-message {
  margin-top: 9px;
}

.unfurl-error-location-trace li,
.unfurl-error-message {
  font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier,
    monospace;
}

.unfurl-error-location-trace ul,
.unfurl-error-location-trace li {
  margin: 0;
}

.e-inline-edit {
  text-decoration: underline;
  cursor: pointer;
}

.footer-separator {
  border-top: 1px solid #999;
  margin: 12px 0 12px 0;
}

.edit-section-footer.failed {
  background-color: rgb(254, 242, 242);
  min-height: initial;
}
</style>
