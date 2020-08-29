<template>
<div :class="{'p-card': true, 'p-entry': true, 'p-hidden': isHidden}">
  <div v-if="entry.type == 'link'">
    <shownotes-link v-bind:entry="entry"></shownotes-link>
  </div>
  <div v-if="entry.type == 'topic'">
    <shownotes-topic v-bind:entry="entry"></shownotes-topic>
  </div>
</div>
</template>

<script>
import ShownotesTopic from "./shownotes/topic";
import ShownotesLink from "./shownotes/link";

export default {
  props: ["entry"],
  data() {
    return {};
  },
  computed: {
    isHidden: function() {
      return this.entry.hidden === "1";
    }
  },
  components: {
    "shownotes-topic": ShownotesTopic,
    "shownotes-link": ShownotesLink
  }
};
</script>

<style>
.p-hidden {
  opacity: 0.7;
  background: #f9f9f9;
}

.p-hidden .p-card-body {
  border-left: 3px solid #999;
}

.p-hidden .p-entry-description,
.failed .p-entry-description {
  border-top: 1px solid #999;
  padding-top: 9px;
}

/* .p-entry:hover {
  background: hsl(197, 90%, 97%);
} */

.p-card-body.p-card-type-topic {
  min-height: inherit;
}

.p-entry-icon {
  width: 16px;
  height: 16px;
}

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
  font-size: 11px;
  line-height: 11px;
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
  min-width: 96px;
  text-align: right;
}

.p-entry-favicon {
  padding-right: 12px;
  display: flex;
  flex-direction: column;
}

.p-entry-url-url,
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

.edit-section .p-entry-description {
  margin-top: 0;
}

.p-entry-url,
.p-entry-site,
.p-entry-description {
  color: #999;
}

.p-expand {
  display: flex;
  width: 100%;
  justify-content: space-around;
  margin: 36px 0;
}

.p-new-entry {
  width: 100%;
}

.p-new-entry h3 {
  margin: 0;
  padding-bottom: 9px;
  color: #999;
}

.p-new-entry-form {
  display: flex;
}

.p-new-entry-form input[type="text"] {
  margin-right: 9px;
}

.p-entry-type-selector {
  margin-bottom: 9px;
}

.p-entry-type-selector span {
  margin-right: 9px;
}

.topic-content {
  color: #333;
  font-weight: bold;
}

.p-entry input,
.p-entry textarea {
  width: 100%;
}
</style>
