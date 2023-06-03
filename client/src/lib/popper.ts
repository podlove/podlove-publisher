import { ref, onMounted, watchEffect } from "vue";
import { createPopper, Options } from "@popperjs/core";

export function usePopper(options: Partial<Options>) {
  let reference = ref<{ el: HTMLElement } | null>(null);
  let popper = ref<{ el: HTMLElement } | null>(null);

  onMounted(() => {
    watchEffect((onInvalidate) => {
      if (!popper.value) return;
      if (!reference.value) return;

      let popperEl = popper.value.el || popper.value;
      let referenceEl = reference.value.el || reference.value;

      if (!(referenceEl instanceof HTMLElement)) return;
      if (!(popperEl instanceof HTMLElement)) return;

      let { destroy } = createPopper(referenceEl, popperEl, options);

      onInvalidate(destroy);
    });
  });

  return [reference, popper];
}
