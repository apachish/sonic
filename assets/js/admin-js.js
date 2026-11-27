jQuery(document).ready(function ($) {
  const q = (sel) => document.querySelector(sel);
  const qa = (sel) => document.querySelectorAll(sel);

  const mainForm = q("#formSteps");
  if (mainForm) {
    const formContent = q("#formContent");
    const formLabels = qa(".form__label--title-question");
    const nextBtn = q(".next-btn");
    const prevBtn = q(".prev-btn");
    const navBlock = q(".btn-cnt");
    const horizontalSteps = qa(".steps i");
    const verticalStepsContainer = q("#verticalSteps");
    const verticalSteps = verticalStepsContainer.querySelectorAll("i");

    let currentStage = 0,
      currentSubStep = 0;
    let tags = {};

    const calcSubSteps = (stage) =>
      Math.max(
        ...[...qa(`.form__label--title-question[data-stage="${stage}"]`)].map(
          (x) => parseInt(x.dataset.substep) || 0
        )
      ) + 1;

    const updateStepUI = () => {
      selectStep(currentSubStep + 1);
      nextBtn.textContent =
        currentSubStep === calcSubSteps(currentStage) - 1
          ? `${NIKSEO.final_Submit}`
          : `${NIKSEO.next_step}`;
      prevBtn.disabled = currentSubStep === 0;
    };

    const selectStep = (step) => {
      removeAlert();
      currentSubStep = step - 1;
      formContent.classList.add("fade");

      setTimeout(() => {
        formLabels.forEach((label) =>
          label.classList.toggle(
            "active",
            parseInt(label.dataset.stage) === currentStage &&
              parseInt(label.dataset.substep) === currentSubStep
          )
        );
        formContent.classList.remove("fade");
      }, 300);

      horizontalSteps.forEach((el, i) =>
        el.classList.toggle("active", i === currentStage)
      );
      verticalSteps.forEach((e) =>
        e.classList.toggle("active", e.dataset.step == step)
      );

      nextBtn.textContent =
        currentSubStep === verticalSteps.length - 1
          ? `${NIKSEO.final_Submit}`
          : `${NIKSEO.next_step}`;
      prevBtn.disabled = currentSubStep === 0;
    };

    const getFormData = () => {
      const fd = new FormData(mainForm);
      return [...fd.entries()].reduce((acc, [k, v]) => {
        if (["cats[]", "suggestedTopics", "targetAudience"].includes(k)) {
          (acc[k] ??= []).push(v);
        } else {
          acc[k] = v;
        }
        return acc;
      }, {});
    };

    verticalSteps.forEach((e) =>
      e.addEventListener("click", () => selectStep(e.dataset.step))
    );

    nextBtn.addEventListener("click", async () => {
      removeAlert();
      if (currentSubStep < calcSubSteps(currentStage) - 1) {
        currentSubStep++;
        updateStepUI();
      } else {
        const data = getFormData();
        toggleProgress(data["request-type"]);
        try {

          const res = await postData(data);
          console.log(res)
          // console.log(NIKSEO.sonic_posts);
          showResult(res);
        } catch (err) {
          console.error("Submission error:", err);
        }
        toggleProgress(data["request-type"]);
      }
    });

    prevBtn.addEventListener("click", () => {
      removeAlert();
      if (currentSubStep > 0) {
        currentSubStep--;
        updateStepUI();
      }
    });

    const toggleProgress = (reqType) => {
      if (reqType === "single-request")
        q(".single-request").classList.toggle("hide");
    };

    const removeAlert = () =>
      q(".progress-block .result").classList.add("hide");

    const showResult = (res) => {
      const resultEl = q(".progress-block .result");
      resultEl.classList.remove("hide");
      const body = res.data.response;
      if (res.success) {
        prepareSuccess();
        formContent.setAttribute("style","justify-content: center")
        resultEl.style.cssText = "grid-column: span 2; text-align: center;";

        resultEl.innerHTML = `
            <div class='alert alert-success' style="text-align:center;">
                <img src='${NIKSEO.assets_url}/img/success.png'>
                <p>${body.message}</p>
                <a href="${NIKSEO.sonic_posts}" class="sonik-view-post" target="_blank">
                   ${NIKSEO.show_posts}
                </a>
            </div>`;
        mainForm.style.display = "none";
        formContent.appendChild(resultEl);
      } else {
        resultEl.innerHTML = `
            <div class='alert alert-error'>
                <p>${body?.message || res.data.message}</p>
            </div>`;
      }
    };

    const prepareSuccess = () => {
      formLabels.forEach((e) => e.classList.remove("active"));
      navBlock.classList.add("hide");
      verticalStepsContainer.classList.add("hide");
    };

    const postData = (input) =>
      $.post(NIKSEO.ajax_url, {
        action: "data_receiver",
        type: "single-article",
        keywords: tags.keywords,
        ...input,
      });

    // tags
    const doesTagExist = (tag, type) => tags[type]?.includes(tag);

    $(document).on("keyup", ".tag-input", function (e) {
      const type = $(this).data("tag-type");
      const tagname = $(this).val().replace(",", "").trim();
      if (
        e.keyCode === 13 &&
        tagname.length > 1 &&
        !doesTagExist(tagname, type)
      ) {
        addTag(tagname, type, $(this));
        //empty the tag input
        $(".tag-input").val("");
      }
    });

    const addTag = (tagname, type, $input) => {
      const tagId = tagname.replaceAll(" ", "-");
      $input.siblings(".tags-wrapper").append(`
                <div id="${type}-${tagId}" class="tag mt-2" data-tag-type="${type}" data-tag-name="${tagname}">
                    <span class="remove-tag" style="cursor:pointer;">×</span> ${tagname}
                </div>`);
      (tags[type] ??= []).push(tagname);
    };

    $(document).on("click", ".remove-tag", function () {
      const $tag = $(this).closest(".tag");
      tags[$tag.data("tag-type")] = tags[$tag.data("tag-type")].filter(
        (t) => t !== $tag.data("tag-name")
      );
      $tag.remove();
    });

    // links
    let linkCount = qa(".g-row").length;
    q(".g-add-row")?.addEventListener("click", () => {
      linkCount++;
      const row = document.createElement("div");
      row.className = "g-row link-list";
      row.innerHTML = `
                <label> ${NIKSEO.link_title} <input required type='text' name='relevant-title[${linkCount}]'></label>
                <label> ${NIKSEO.destination_url} <input required type='text' name='relevant-link[${linkCount}]'></label>
                <a class="g-delete remove-btn">&times;</a>`;
      row.querySelector(".g-delete").addEventListener("click", (e) => {
        e.preventDefault();
        row.remove();
      });
      q(".related-links").appendChild(row);
    });
  }

  // posts update
  const updatePopup = q("#update-popup");
  if (updatePopup) {
    const postIdInput = q("#popup-post-id");
    const warning = q("#whole-warning");
    const alertBox = updatePopup.querySelector(".alert");
    const updateButtons = qa(".open-update-popup");

    updateButtons.forEach((btn) =>
      btn.addEventListener("click", () => {
        resetAlert(alertBox);
        postIdInput.value = btn.dataset.postId;
        updatePopup.style.display = "flex";
      })
    );

    q(".close-popup").addEventListener(
      "click",
      () => (updatePopup.style.display = "none")
    );

    qa('input[name="update_type"]').forEach((r) =>
      r.addEventListener("change", () => {
        warning.style.display = r.value === "whole" ? "block" : "none";
      })
    );

    q("#update-form").addEventListener("submit", function (e) {
      e.preventDefault();
      const postId = postIdInput.value;
      const updateType = this.querySelector(
        'input[name="update_type"]:checked'
      ).value;
      const model = this.querySelector('input[name="model"]:checked').value;
      const spinner = this.querySelector(".nik-spinner");
      const submitBtn = this.querySelector(".button-primary");
      resetAlert(alertBox);
      spinner.classList.toggle("hide");
      submitBtn.disabled = true;
      const postRow = document.querySelector(`#post_${postId}`);
      let updatingTableBody = document.querySelector(
        ".sonik-posts-container.updating tbody"
      );

      // Ensure Updating Posts container exists; create if absent
      if (!updatingTableBody) {
        const container = document.createElement("div");
        container.className = "sonik-posts-container updating";
        container.style.marginBottom = "20px";
        container.innerHTML = `
            <h1>${(NIKSEO && (NIKSEO.updating_posts || NIKSEO.UpdatingPosts)) || "Updating Posts"}</h1>
            <table class="wp-list-table widefat fixed striped">
              <thead>
                <tr>
                  <th>${NIKSEO.title}</th>
                  <th>${NIKSEO.publish_date}</th>
                  <th>${NIKSEO.modify_date}</th>
                  <th>${NIKSEO.author}</th>
                  <th>${NIKSEO.actions}</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>`;
        const allPostsContainer = document.querySelector(
          ".sonik-posts-container.total-posts"
        );
        if (allPostsContainer && allPostsContainer.parentNode) {
          allPostsContainer.parentNode.insertBefore(container, allPostsContainer);
        } else {
          // Fallback: append to main wrapper
          const wrap = document.querySelector(".wrap.sonik-body") || document.body;
          wrap.insertBefore(container, wrap.firstChild);
        }
        updatingTableBody = container.querySelector("tbody");
      }

      if (postRow && updatingTableBody) {
        postRow.remove();
        const newRow = document.createElement("tr");
        newRow.id = `post_${postId}`;
        newRow.innerHTML = `
      <td><a target="_blank" href="${postRow.querySelector("a").href}">
        ${postRow.querySelector("a").textContent}</a></td>
      <td>${postRow.children[1].textContent}</td>
      <td>${postRow.children[2].textContent}</td>
      <td>${postRow.children[3].textContent}</td>
      <td class="nik-actions">
        <button class="button open-cancel-update-popup" data-post-id="${postId}">
           ${NIKSEO.cancel_process}
        </button>
        <span class="nik-spinner"></span>
      </td>
    `;
        updatingTableBody.prepend(newRow);
        // Reveal and focus the updating table
        const updatingContainer = updatingTableBody.closest(".sonik-posts-container.updating");
        if (updatingContainer) {
          updatingContainer.style.display = "";
          updatingContainer.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }

      $.post(NIKSEO.ajax_url, {
        action: "data_receiver",
        type: "update",
        postId: [postId],
        model: [model],
        updateType,
      })
        .done((res) => {
          enableOtherUpdates();
          spinner.classList.toggle("hide");
          submitBtn.disabled = false;
          alertBox.classList.remove("hide");
          alertBox.classList.add(
            res.data.status === "success" ? "success" : "danger"
          );
          alertBox.innerText = res.data.response.message;
          if (res.data.status === "success") {
            markUpdated(postId);
            updatePopup.style.display = "none";
          }
        })
        .fail(() => {
          enableOtherUpdates();
          spinner.classList.toggle("hide");
          submitBtn.disabled = false;
        });
    });

    const cancelUpdatePopup = q("#cancel-update-popup");
    // const cancelUpdateButtons = qa(".open-cancel-update-popup");
    const cancelPostIdInput = q("#cancel-popup-post-id");
    $(document).on("click", ".open-cancel-update-popup", function () {
      cancelUpdatePopup.style.display = "flex";
      cancelPostIdInput.value = this.dataset.postId;
    });


    q(".close-cancel-popup").addEventListener(
      "click",
      () => (cancelUpdatePopup.style.display = "none")
    );
    q("#cancel-update-form").addEventListener("submit", function (e) {
      e.preventDefault();

      const canceledIds = [cancelPostIdInput.value];

      $.post(NIKSEO.ajax_url, {
        action: "data_receiver",
        type: "cancel-update",
        postId: canceledIds,
      })
        .done((res) => {
          // console.log("Cancel response:", res);

          if (res.success && res.data.status === "success") {
            cancelUpdatePopup.style.display = "none";

            canceledIds.forEach((id) => {
              const postRow = document.querySelector(
                `.sonik-posts-container.updating #post_${id}`
              );
              if (postRow) {
                postRow.style.transition = "opacity 0.3s ease";
                postRow.style.opacity = "0";
                setTimeout(() => {
                  const tbody = postRow.parentElement;
                  postRow.remove();
                  // If no rows remain in the updating table, remove the whole container
                  if (tbody && tbody.children.length === 0) {
                    const container = tbody.closest('.sonik-posts-container.updating');
                    if (container) {
                      container.style.transition = 'opacity 0.3s ease';
                      container.style.opacity = '0';
                      setTimeout(() => container.remove(), 300);
                    }
                  }
                }, 300);
              }
            });

            canceledIds.forEach((id) => {
              const btn = document.querySelector(
                `.open-cancel-update-popup[data-post-id="${id}"]`
              );
              if (btn) {
                btn.textContent = NIKSEO.canceled || "Canceled";
                btn.disabled = true;
                Object.assign(btn.style, {
                  backgroundColor: "#ccc",
                  color: "#263646",
                  cursor: "default",
                  border: "2px solid #ccc",
                });
              }
            });
          } else {
            console.warn("Cancel failed:", res);
          }
        })
        .fail((err) => {
          console.error("Cancel AJAX failed!", err);
        });
    });

    const markUpdated = (id) => {
      const tagEl = q(`#post_${id} .tag`);
      if (tagEl) {
        tagEl.className = "tag success-bg";
        tagEl.innerText = `${NIKSEO.recently_updated}`;
      }
    };

    const resetAlert = (a) => {
      a.className = "alert hide";
      a.innerText = "";
    };

    const enableOtherUpdates = () => {
      updateButtons.forEach((btn) => {
        btn.disabled = false;
        btn.closest("tr").querySelector(".nik-spinner").classList.add("hide");
      });
    };

   function checkProcessingPosts() {
  const $tableWrapper = jQuery(".updating table.wp-list-table").closest(".updating");
  const $rows = jQuery('table.wp-list-table tbody tr[id^="post_"]');
  const processing_posts = [];

  $rows.each(function () {
    const id = jQuery(this).attr("id").replace("post_", "");
    processing_posts.push(id);
  });

  
  if (processing_posts.length === 0) {
    // console.log("✅ No processing posts — keeping table visible");
    return;
  }

  jQuery
    .post(NIKSEO.ajax_url, {
      action: "data_receiver",
      type: "check_status",
      postId: processing_posts,
    })
    .done(function (res) {
  // console.log("🔄 Status check result:", res);

  let completed = false;
  let serverIds = [];

  // ✅ اگر پاسخ موفق بود و داده آرایه است
  if (res.success && Array.isArray(res.data.posts)) {
    serverIds = res.data.posts.map(id => String(id));
  } else {
    // ❌ اگر success=false و پیام "هیچ پستی یافت نشد" بود، یعنی هیچ پست Processing نیست
    if (res.data && res.data.status === "failed") {
      completed = true;
    } else {
      console.warn("⚠️ Invalid response, keeping table intact");
      return;
    }
  }

  jQuery('.updating table.wp-list-table tbody tr[id^="post_"]').each(function () {
    const $row = jQuery(this);
    const rowId = $row.attr("id").replace("post_", "");

    // ✅ اگر در حالت completed یا آیدی داخل آرایه نیست
    if (completed || !serverIds.includes(rowId)) {
      const $spinner = $row.find(".nik-spinner");
      const $button = $row.find(".open-cancel-update-popup");

      $spinner.fadeOut(300, () => $spinner.remove());

      $button.text(`${NIKSEO.Updated}`);
      $button.prop("disabled", true);
      $button.addClass("updated-state").css({
        backgroundColor: "#1345dc",
        color: "#fff",
        cursor: "default",
        border: "2px solid #1345dc",
      });

      $row.css("background-color", "#eaf5ee");
    }
  });
})


    .fail(function (err) {
      console.warn("🚨 AJAX status check failed:", err);
    });
}



    // Run every 5 seconds
    setInterval(checkProcessingPosts, 5000);
  }
  // hints
  let sonic_hint = document.querySelector(".disable-hint")
  if(sonic_hint){

    // show hint
    setTimeout( () => {
      document.querySelector(".sonic-hint").classList.remove("hide")
    },1000);

    sonic_hint.addEventListener("click", e => {
      closeHint();
      $.ajax({
        url: NIKSEO.ajax_url,
        type: 'post',
        data: {
          action: 'data_receiver',
          type: 'disable_hint',
          id: sonic_hint.getAttribute("hint_id")
        },
        success: function(response){
          console.log(response)
        },
        error: function(response){
          console.log(response)
        }
      })
    })
  }

  // keyword research
  let keyword_research_form = document.querySelector("#keyword-research")
  if(keyword_research_form){
    const keyword_research_result = document.querySelector("#keyword-research-result")
    const main_loader = document.querySelector(".nik-spinner")
    keyword_research_form.addEventListener("submit", function(e){
      let model = document.querySelector("#keyword-research input[name=model]:checked").value
      let language = document.getElementById('language-select').value;
      e.preventDefault();
      main_loader.classList.remove("hide")
      $.ajax({
        url: NIKSEO.ajax_url,
        type: 'post',
        data: {
          action: 'data_receiver',
          type: 'keyword_research',
          model: model ?? 'gpt',
          lang: language
        },
        success: function(response){
          console.log(response)
          main_loader.classList.add("hide")
          if(response.success){
            show_keyword_research_result(response.data.response.keywords)
          }else{
            show_keyword_research_result(response.data.response.message, 'failed')
          }
        },
        error: function(response){
          main_loader.classList.add("hide")
          console.log(response)
        }
      })
    })

    function show_keyword_research_result(result, status = 'success'){
      keyword_research_result.classList.remove("hide")
      if(status === 'failed'){
        keyword_research_result.innerHTML = "<div class='nik-alert'>" + result + "</div>"
        return;
      }
      // safety warning for closing screen
      window.addEventListener("beforeunload", beforeUnloadHandler);

      document.querySelector(".sonic-container .download-excel").classList.remove("hide")
      let table_html = "";
      table_html = "<table id='research-table'><thead><tr><th>عنوان پیشنهادی</th><th class='actions'>عملیات</th></tr></thead><tbody>"
      let i = 1;
      result.forEach( e => {
        let id = "row_" + i
        table_html += '<tr id="' + id +'">'
        table_html += '<td>' + e + '</td>';
        table_html += '<td class="actions"><button onclick="keywordModal(\''+ e +'\', \'' + id + '\')">'+ NIKSEO.create_post +'</button></td>';
        table_html += '</tr>'
        i++
      })
      table_html += "</tbody></table>";

      keyword_research_result.classList.remove("hide")
      keyword_research_result.innerHTML = table_html
    }

    const create_post_form = document.querySelector("#create-post-by-keyword")
    create_post_form.addEventListener("submit", e => {
      let title = create_post_form.querySelector("input[name=title]").value
      let model = create_post_form.querySelector("input[name=model]:checked").value
      let row_id = create_post_form.querySelector("input[name=row_id]").value

      let language = document.getElementById('language-select').value;
      let featured_image = create_post_form.querySelector("input[name='featured-image']");
      if (featured_image && featured_image.checked) {
        featured_image = 1;
      } else {
        featured_image = 0;
      }

      let cats_select = create_post_form.querySelectorAll(".category-select input:checked");
      let cats = [];
      if(cats_select.length > 0){
        cats_select.forEach( e => {
          cats.push( e.value );
        })
      }
      post_create_processing(create_post_form, true);
      e.preventDefault()
      $.ajax({
        url: NIKSEO.ajax_url,
        type: 'post',
        data: {
          action: 'data_receiver',
          type: 'single-article',
          title: title,
          model: model ?? 'gpt',
          lang: language ?? 'farsi',
          featuredImage: featured_image,
          cats: cats
        },
        success: function(response){
          post_create_processing(create_post_form, false);
          if(response.success){
            keyword_post_create_result(response.data.response.message, row_id);
            closeModal("create-post-by-keyword-modal")
            showToast()
          }else{
            keyword_post_create_result(response.data.response.message)
          }
        },
        error: function(response){
          post_create_processing(create_post_form, false);
          keyword_post_create_result(response.data.response.message)
        }
      })
    })
  }

  // site description
  let sonic_settings = document.querySelector(".sonic-settings")
  if(sonic_settings) {
    const suggest_description = document.querySelector("#sonic_site_description_suggest")
    const description_input = document.getElementById("sonic-site-description")
    const main_loader = sonic_settings.querySelector(".nik-spinner")
    suggest_description.addEventListener("click", function (e) {
      main_loader.classList.remove("hide")
      $.ajax({
        url: NIKSEO.ajax_url,
        type: 'post',
        data: {
          action: 'data_receiver',
          type: 'site_description',
          model: 'gemini',
        },
        success: function (response) {
          console.log(response)
          main_loader.classList.add("hide")
          if (response.success) {
            description_input.value = response.data.response.siteDescription
            suggest_description.classList.add("hide")
            description_input.classList.add("updated")
          } else {
            alert("error")
          }
        },
        error: function (response) {
          main_loader.classList.add("hide")
          console.log(response)
        }
      })
    })
  }

});

function keyword_post_create_result(message, row_id = null, close = false){
  let modal = document.querySelector("#create-post-by-keyword-modal");
  let alert = modal.querySelector(".alert")
  if(row_id){
    document.querySelector("#" + row_id + " td.actions").innerHTML = "<span class='alert alert-success'>" + NIKSEO.post_requested + "</span>"
  }
  if(!close){
    alert.classList.remove("hide")
    alert.innerText = message;
  }else{
    closeModal('create-post-by-keyword-modal')
  }
}

function post_create_processing(modal, status = true){
  let loader = modal.querySelector(".nik-spinner")
  let submit_button = modal.querySelector("#keyword_create_post")
  if(status){
    loader.classList.remove("hide")
    submit_button.classList.add("hide")
  }else{
    loader.classList.add("hide")
    submit_button.classList.remove("hide")
  }
}

function reset_modal(modal){
  modal.querySelector(".alert").classList.add("hide")
  modal.querySelector("#keyword_create_post").classList.remove("hide")
}

function keywordModal(keyword, row_id){
  let modal = document.querySelector("#create-post-by-keyword-modal")
  let adv_create_button = document.querySelector("#advanced-content-create-button")
  adv_create_button.setAttribute("href", NIKSEO.content_create + "&title=" + encodeURIComponent(keyword))
  adv_create_button.setAttribute("onclick", "keyword_post_create_result('ok', \'" + row_id + "\' , true)")
  modal.style.display = "flex";
  modal.querySelector("input[name=title]").value = keyword
  modal.querySelector("input[name=row_id]").value = row_id
}

function closeModal(id){
  let modal = document.querySelector("#" + id)
  reset_modal(modal)
  modal.style.display = "none"
}

function showToast(message = "", type = "success") {
  const toast = document.getElementById("toast");
  if(message){
    const toast_message = toast.querySelector("span")
    toast_message.textContent = message;
  }
  toast.className = "";
  toast.classList.add("show", type);

  setTimeout(() => {
    toast.classList.remove("show");
  }, 5000)
}

function toggle_settings_tab(el){
  el.classList.toggle("active")
  let settings_block = document.querySelector("#sonic-modal-adv-settings")
  if(settings_block){
    settings_block.classList.toggle("hide")
  }
}

function downloadCSV(table_id, columns = [0], row_number = false) {
  const table = document.getElementById(table_id);
  if(table){
    let csv = [];
    const rows = table.querySelectorAll("tr");

    // Add UTF-8 BOM to support Persian in Excel
    csv.push('\uFEFF');

    for (let i = 0; i < rows.length; i++) {
      const row = [];
      const cols = rows[i].querySelectorAll("td, th");

      // show row number
      if(row_number){
        if(i === 0){
          row.push('"#"');
        }else{
          row.push('"' + i + '"');
        }
      }

      if(columns.length > 0){
        // select proper columns
        columns.forEach( e => {
          let text = cols[e].innerText
          row.push('"' + text + '"');
        })
      }else{
        // select all columns
        for (let j = 0; j < cols.length; j++) {
          let text = cols[j].innerText;

          row.push('"' + text + '"');
        }
      }

      csv.push(row.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    const downloadLink = document.createElement("a");
    downloadLink.download = "sonic_keyword_research_result.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);

    // disable close screen warning
    disableWarning();
  }
}

function closeHint(){
  const hint = document.querySelector(".sonic-hint")
  if(hint){
    hint.classList.add("hide")
  }
}
function beforeUnloadHandler(e) {
  e.preventDefault();
}
// Disable warning
function disableWarning() {
  window.removeEventListener("beforeunload", beforeUnloadHandler);
}