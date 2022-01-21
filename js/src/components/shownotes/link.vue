<template>
  <div>
    <link-unfurling v-if="entry.state == 'unfurling'" />
    <link-compact
      v-else-if="!edit"
      v-bind:entry="entry"
      :icon="icon"
      :is-hidden="isHidden"
      v-on:toggleHidden="toggleHide()"
      v-on:enableEdit="edit = true"
    />
    <!-- form -->
    <sn-card v-else>
      <div v-if="entry.image">
        <label class="block text-sm font-medium text-gray-700">
          Thumbnail
        </label>
        <div class="mt-1 flex">
          <img class="max-h-48 rounded" :src="entry.image" />
        </div>

        <div class="h-6"></div>
      </div>

      <div class="">
        <label for="url" class="block text-sm font-medium text-gray-700">
          URL
        </label>
        <div class="mt-1">
          <input
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.url"
            type="text"
            name="url"
            id="url"
            placeholder="URL"
            class="
              shadow-sm
              focus:ring-blue-500 focus:border-blue-500
              block
              w-full
              sm:text-sm
              border border-gray-300
              rounded-md
            "
          />

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
      </div>

      <div class="mt-6">
        <label for="sitename" class="block text-sm font-medium text-gray-700">
          Site Name
        </label>
        <div class="mt-1">
          <input
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.site_name"
            type="text"
            name="sitename"
            id="sitename"
            class="
              shadow-sm
              focus:ring-blue-500 focus:border-blue-500
              block
              w-full
              sm:text-sm
              border border-gray-300
              rounded-md
            "
          />

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
      </div>

      <div class="mt-6">
        <label for="title" class="block text-sm font-medium text-gray-700">
          Title
        </label>
        <div class="mt-1">
          <input
            @keydown.enter.prevent="save()"
            @keydown.esc="edit = false"
            v-model="entry.title"
            type="text"
            name="title"
            id="title"
            class="
              shadow-sm
              focus:ring-blue-500 focus:border-blue-500
              block
              w-full
              sm:text-sm
              border border-gray-300
              rounded-md
            "
          />

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
      </div>

      <div class="mt-6">
        <label
          for="description"
          class="block text-sm font-medium text-gray-700"
        >
          Description
        </label>
        <div class="mt-1">
          <textarea
            v-model="entry.description"
            id="description"
            name="description"
            rows="3"
            class="
              shadow-sm
              focus:ring-blue-500 focus:border-blue-500
              block
              w-full
              sm:text-sm
              border border-gray-300
              rounded-md
            "
          />

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
      </div>

      <div class="h-8 w-full border-b border-gray-300"></div>

      <div class="pt-5">
        <div class="flex justify-between">
          <div>
            <sn-button type="danger" :onClick="deleteEntry"
              >Delete Entry</sn-button
            >
          </div>
          <div>
            <div class="flex justify-end">
              <sn-button
                :onClick="
                  () => {
                    edit = false;
                  }
                "
                >Cancel</sn-button
              >
              <sn-button :onClick="unfurl" htmlClass="ml-3"
                >Autofill Metadata</sn-button
              >
              <sn-button :onClick="save" type="primary" htmlClass="ml-3"
                >Save</sn-button
              >
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="entry.state == 'failed'"
        class="h-6 w-full border-b border-gray-300"
      ></div>

      <div
        v-if="entry.state == 'failed'"
        class="mt-6 bg-red-50 border-l-4 border-red-400 p-4"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 text-red-400"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <div class="text-sm text-red-700">
              <p class="font-bold">
                Unable to access URL:
                <a :href="entry.original_url" target="_blank">{{
                  entry.original_url
                }}</a>
              </p>

              <p v-if="error_message">
                {{ error_message }}
              </p>

              <div v-if="trace_locations && trace_locations.length > 0">
                <strong>Location Trace:</strong>

                <ul>
                  <li
                    v-for="(location, index) in trace_locations"
                    :key="location"
                  >
                    {{ index }}: {{ location }}
                  </li>
                </ul>
              </div>
            </div>

            <div class="mt-6">
              <label
                for="original_url"
                class="block text-sm font-medium text-gray-700"
              >
                Original URL
              </label>
              <div class="mt-1">
                <input
                  @keydown.enter.prevent="saveOriginalUrl()"
                  @keydown.esc="edit = false"
                  v-model="entry.original_url"
                  type="text"
                  name="original_url"
                  id="original_url"
                  class="
                    shadow-sm
                    focus:ring-blue-500 focus:border-blue-500
                    block
                    w-full
                    sm:text-sm
                    border-gray-300
                    rounded-md
                  "
                />
              </div>
            </div>

            <div class="mt-6 flex justify-end">
              <sn-button :onClick="saveOriginalUrl">
                Save and Unfurl
              </sn-button>
            </div>
          </div>
        </div>
      </div>
    </sn-card>
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

import SNButton from "./sn-button.vue";
import SNCard from "./sn-card.vue";
import LinkCompact from "./link-compact.vue";
import LinkUnfurling from "./link-unfurling.vue";

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
    "sn-button": SNButton,
    "sn-card": SNCard,
    "link-compact": LinkCompact,
    "link-unfurling": LinkUnfurling,
  },
  computed: {
    icon: function () {
      if (this.entry.icon && this.entry.icon[0] == "/") {
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

.sortable-chosen.p-card {
  background: #dde1ec;
  border-color: black;
}

.sortable-chosen .p-entry-content .p-entry-thumbnail,
.sortable-chosen .p-entry-content .p-entry-description,
.sortable-chosen .p-entry-content .p-entry-url-url {
  display: none !important;
}
</style>
