;(function () {
  const faLocaleDefinition = window.flatpickr.l10ns.fa

  if (
    typeof window.flatpickr === "undefined" ||
    typeof window.flatpickr.l10ns === "undefined" ||
    typeof window.flatpickr.l10ns.fa === "undefined"
  ) {
    console.error(
      "JalaliDate: Locale 'fa' definition not found on window.flatpickr."
    )
    return
  }

  document.addEventListener("DOMContentLoaded", () => {
    initJalaliDatepickers(document)
    hookModalEvents()
  })

  if (typeof Livewire !== "undefined") {
    document.addEventListener("livewire:load", () => {
      Livewire.hook("morph.mounted", ({ el }) => {
        initJalaliDatepickers(el)
      })
    })
  }

  function initJalaliDatepickers(scope) {
    const datepickers = scope.querySelectorAll("input.flatpickr-input")
    if (!datepickers.length) return

    datepickers.forEach(function (input) {
      let elementWithInstance = input.parentElement

      if (elementWithInstance.classList.contains("jalali-initialized")) return

      const fp = elementWithInstance._flatpickr
      if (fp && typeof fp.set === "function") {
        fp.set("locale", faLocaleDefinition)
      }

      elementWithInstance.classList.add("jalali-initialized")
    })
  }

  function hookModalEvents() {
    const originalAxiosGet = window.axios.get

    window.axios.get = function (url, ...args) {
      const isModalRequest = url.includes("/modals/")

      if (isModalRequest) {
        return originalAxiosGet.apply(this, [url, ...args]).then((response) => {
          if (response.data.html.includes("akaunting-date")) {
            setTimeout(() => {
              const modals = document.querySelectorAll("[data-modal-handle]")
              if (modals.length) {
                modals.forEach(function (modal) {
                  initJalaliDatepickers(modal)
                })
              }
            }, 200)
          }
          return response
        })
      }

      return originalAxiosGet.apply(this, [url, ...args])
    }
  }
})()
