;(function () {
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

  let datePickers = []
  function updateAllJalaliDatepickers() {
    datePickers.forEach(function (pair) {
      updateJalaliAttr(pair.instance, pair.jalali)
    })
  }
  function updateJalaliAttr(instance, input) {
    const localeDateStringOptions = {
      numberingSystem: "latn",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }

    let minDate = instance.config.minDate
    let maxDate = instance.config.maxDate
    let originDate = instance.selectedDates[0]

    if (minDate) {
      input.setAttribute(
        "data-jdp-min-date",
        minDate.toLocaleDateString("fa-IR", localeDateStringOptions)
      )
    }
    if (maxDate) {
      input.setAttribute(
        "data-jdp-max-date",
        maxDate.toLocaleDateString("fa-IR", localeDateStringOptions)
      )
    }

    input.value = originDate.toLocaleDateString(
      "fa-IR",
      localeDateStringOptions
    )
  }

  function initJalaliDatepickers(scope) {
    const datepickers = scope.querySelectorAll("input.flatpickr-input")
    if (!datepickers.length) return

    datepickers.forEach(function (input) {
      let elementWithInstance = input.parentElement

      if (elementWithInstance.classList.contains("jalali-initialized")) return

      const fpInstance = elementWithInstance._flatpickr
      if (fpInstance && typeof fpInstance.set === "function") {
        const jalali = document.createElement("input")

        jalali.classList.add("jalali-datepicker")
        for (const className of input.classList) {
          if (!["datepicker", "input", "active"].includes(className)) {
            jalali.classList.add(className)
          }
        }

        jalali.setAttribute("data-jdp", "")
        updateJalaliAttr(fpInstance, jalali)

        //Prepare Wrapper
        const jalaliWrap = elementWithInstance.cloneNode(true)
        jalaliWrap.querySelectorAll("input").forEach((child) => {
          child.remove()
        })
        jalaliWrap.prepend(jalali)
        elementWithInstance.insertAdjacentElement("afterend", jalaliWrap)

        //Datepicker Switcher
        let parentElement = elementWithInstance.parentElement
        let jalaliDateTgl = parentElement.nextElementSibling
        if (jalaliDateTgl.classList.contains("datepicker-switcher")) {
          parentElement.appendChild(jalaliDateTgl)
          jalaliDateTgl.style.display = ""
          const jalaliDateTglInp = jalaliDateTgl.querySelectorAll("input")[0]
          if (jalaliDateTglInp.checked) {
            jalaliWrap.style.display = ""
            elementWithInstance.style.display = "none"
          } else {
            elementWithInstance.style.display = ""
            jalaliWrap.style.display = "none"
          }
          jalaliDateTglInp.addEventListener("change", function () {
            if (this.checked) {
              jalaliWrap.style.display = ""
              elementWithInstance.style.display = "none"
            } else {
              elementWithInstance.style.display = ""
              jalaliWrap.style.display = "none"
            }
          })
        }

        //on jalali datepicker changed
        jalali.addEventListener("change", function () {
          let parts = this.value.split("/")

          // Convert Jalali date to a standard Date object
          let newDate = jalaali.jalaaliToDateObject(
            parseInt(parts[0]),
            parseInt(parts[1]),
            parseInt(parts[2])
          )

          fpInstance.setDate(newDate, true)
        })

        //on original datepicker changed
        fpInstance.config.onChange.push(function (
          selectedDates,
          dateStr,
          instance
        ) {
          setTimeout(updateAllJalaliDatepickers, 100)
        })

        elementWithInstance.classList.add("jalali-initialized")
        datePickers.push({ instance: fpInstance, jalali: jalali })
      }
    })

    jalaliDatepicker.startWatch({
      //selector: "input.jalali_datetimepicker",
      minDate: "attr",
      maxDate: "attr",
      time: false,
      persianDigits: false,
      showEmptyBtn: false,
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
