/**
 * Admin JavaScript for Custom CMS
 */
(function ($) {
  "use strict";

  // Add this to the beginning of your admin.js file to check if jQuery is loaded
  if (typeof jQuery === "undefined") {
    console.error(
      "jQuery is not loaded, which is required for the Custom CMS plugin"
    );
  }

  // Ensure Handlebars is available before using it
  if (typeof Handlebars === "undefined") {
    console.error(
      "Handlebars is not loaded, which is required for the category template"
    );
  }

  // Add console logging to debug the template compilation
  var categoryTemplate = document.getElementById("category-item-template");
  if (!categoryTemplate) {
    console.error("Category item template not found in the DOM");
  }

  // Global variables
  var restUrl = customCmsData.restUrl;
  var restNonce = customCmsData.nonce;

  // Handlebars helpers
  if (typeof Handlebars !== "undefined") {
    Handlebars.registerHelper("if", function (conditional, options) {
      if (conditional) {
        return options.fn(this);
      } else {
        return options.inverse(this);
      }
    });
  }

  /**
   * Pages List functionality
   */
  var PagesList = {
    init: function () {
      if (!$("#custom-cms-pages-list").length) {
        return;
      }

      this.currentPage = 1;
      this.perPage = 10;
      this.selectedCategory = '';

      this.setupTemplates();
      this.loadCategories();
      this.loadPages();
      this.bindEvents();
    },

    setupTemplates: function () {
      var source = $("#page-item-template").html();
      this.template = Handlebars.compile(source);
    },

    loadCategories: function () {
      var self = this;
      $.ajax({
        url: restUrl + "/categories",
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (categories) {
          var $dropdown = $("#category-filter-dropdown");
          $dropdown.find("option:not(:first)").remove();
          categories.forEach(function (category) {
            var label = category.name + (category.page_count ? " (" + category.page_count + ")" : "");
            $dropdown.append('<option value="' + category.id + '">' + label + '</option>');
          });
        },
      });
    },

    loadPages: function () {
      var self = this;
      var data = {
        page: self.currentPage,
        per_page: self.perPage,
      };
      var selectedCategory = $("#category-filter-dropdown").val();
      if (selectedCategory) {
        data.category_id = selectedCategory;
      }
      $.ajax({
        url: restUrl + "/pages",
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: data,
        success: function (data, textStatus, request) {
          self.renderPages(data);
          var totalItems = request.getResponseHeader("X-WP-Total");
          var totalPages = request.getResponseHeader("X-WP-TotalPages");
          self.updatePagination(totalItems, totalPages);
        },
        error: function (xhr) {
          alert("Error loading pages: " + xhr.responseText);
        },
      });
    },

    renderPages: function (pages) {
      var self = this;
      var $list = $("#the-list");

      $list.empty();

      if (pages.length === 0) {
        $list.html(
          '<tr class="no-items"><td class="colspanchange" colspan="8">No pages found.</td></tr>'
        );
        return;
      }

      $.each(pages, function (index, page) {
        // Add helper properties for template
        page.status_draft = page.status === "draft";
        page.status_published = page.status === "published";

        // Format dates
        page.created = self.formatDate(page.created);
        page.updated = self.formatDate(page.updated);

        // Append to list
        $list.append(self.template(page));
      });
    },

    updatePagination: function (totalItems, totalPages) {
      // Update counts
      $("#items-count, #items-count-bottom").text(totalItems + " items");
      $("#total-pages, #total-pages-bottom").text(totalPages);

      // Update current page input
      $("#current-page-selector, #current-page-selector-bottom").val(
        this.currentPage
      );

      // Update pagination buttons
      var $prevTop = $(".tablenav-pages-navspan").first();
      var $nextTop = $("#next-page");
      var $lastTop = $("#last-page");

      var $prevBottom = $(".tablenav-pages-navspan").eq(2);
      var $nextBottom = $("#next-page-bottom");
      var $lastBottom = $("#last-page-bottom");

      if (this.currentPage === 1) {
        $prevTop.addClass("disabled");
        $prevBottom.addClass("disabled");
      } else {
        $prevTop.removeClass("disabled");
        $prevBottom.removeClass("disabled");
      }

      if (this.currentPage == totalPages) {
        $nextTop.addClass("disabled");
        $lastTop.addClass("disabled");
        $nextBottom.addClass("disabled");
        $lastBottom.addClass("disabled");
      } else {
        $nextTop.removeClass("disabled");
        $lastTop.removeClass("disabled");
        $nextBottom.removeClass("disabled");
        $lastBottom.removeClass("disabled");
      }
    },

    bindEvents: function () {
      var self = this;

      // Pagination
      $("#next-page, #next-page-bottom").on("click", function (e) {
        e.preventDefault();
        if ($(this).hasClass("disabled")) return;

        self.currentPage++;
        self.loadPages();
      });

      $("#last-page, #last-page-bottom").on("click", function (e) {
        e.preventDefault();
        if ($(this).hasClass("disabled")) return;

        var totalPages = parseInt($("#total-pages").text());
        self.currentPage = totalPages;
        self.loadPages();
      });

      // Page status change
      $(document).on("click", ".publish-page", function (e) {
        e.preventDefault();
        var pageId = $(this).data("id");
        self.updatePageStatus(pageId, "published");
      });

      $(document).on("click", ".unpublish-page", function (e) {
        e.preventDefault();
        var pageId = $(this).data("id");
        self.updatePageStatus(pageId, "draft");
      });

      // Delete page
      $(document).on("click", ".delete-page", function (e) {
        e.preventDefault();
        var pageId = $(this).data("id");

        if (
          confirm(
            "Are you sure you want to delete this page? This action cannot be undone."
          )
        ) {
          self.deletePage(pageId);
        }
      });

      // Bulk actions
      $("#doaction, #doaction2").on("click", function (e) {
        e.preventDefault();
        var action = $("#bulk-action-selector-top").val();

        if (action === "-1") {
          return;
        }

        var selectedPages = [];
        $('input[name="page[]"]:checked').each(function () {
          selectedPages.push($(this).val());
        });

        if (selectedPages.length === 0) {
          alert("Please select at least one page.");
          return;
        }

        self.performBulkAction(action, selectedPages);
      });

      $("#category-filter-dropdown").on("change", function () {
        self.currentPage = 1;
        self.loadPages();
      });
    },

    updatePageStatus: function (pageId, status) {
      var self = this;

      $.ajax({
        url: restUrl + "/pages/" + pageId + "/status",
        method: "PUT",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: {
          status: status,
        },
        success: function () {
          self.loadPages();
        },
        error: function (xhr) {
          alert("Error updating page status: " + xhr.responseText);
        },
      });
    },

    deletePage: function (pageId) {
      var self = this;

      $.ajax({
        url: restUrl + "/pages/" + pageId,
        method: "DELETE",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function () {
          self.loadPages();
        },
        error: function (xhr) {
          alert("Error deleting page: " + xhr.responseText);
        },
      });
    },

    performBulkAction: function (action, pageIds) {
      var self = this;
      var totalPages = pageIds.length;
      var processed = 0;

      if (action === "delete") {
        if (
          !confirm(
            "Are you sure you want to delete " +
              totalPages +
              " page(s)? This action cannot be undone."
          )
        ) {
          return;
        }
      }

      // Process each page
      $.each(pageIds, function (index, pageId) {
        var promise;

        if (action === "publish") {
          promise = self.updatePageStatusPromise(pageId, "published");
        } else if (action === "draft") {
          promise = self.updatePageStatusPromise(pageId, "draft");
        } else if (action === "delete") {
          promise = self.deletePagePromise(pageId);
        }

        promise.then(function () {
          processed++;
          if (processed === totalPages) {
            // Reload the pages once all operations are complete
            self.loadPages();
          }
        });
      });
    },

    updatePageStatusPromise: function (pageId, status) {
      return $.ajax({
        url: restUrl + "/pages/" + pageId + "/status",
        method: "PUT",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: {
          status: status,
        },
      });
    },

    deletePagePromise: function (pageId) {
      return $.ajax({
        url: restUrl + "/pages/" + pageId,
        method: "DELETE",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
      });
    },

    formatDate: function (dateString) {
      if (!dateString) return "";

      var date = new Date(dateString);
      return date.toLocaleString();
    },
  };

  /**
   * Page Editor functionality
   */
  var PageEditor = {
    init: function () {
      if (!$("#custom-cms-editor").length) {
        return;
      }

      this.pageId = $("#custom-cms-editor").data("page-id");
      this.initializeFormSections();
      this.bindEvents();
      this.loadCategories();
      this.socialLinkIndex = 0;  // Initialize social link index

      if (this.pageId) {
        this.loadPage(this.pageId);
        $("#delete-page-button").show();
        $("#preview-page").show();
      }
    },

    initializeFormSections: function () {
      // Prepare templates
      this.sectionTemplate = Handlebars.compile($("#section-template").html());
      this.faqTemplate = Handlebars.compile($("#faq-template").html());
      this.videoTemplate = Handlebars.compile($("#video-template").html());
      this.breadcrumbTemplate = Handlebars.compile(
        $("#breadcrumb-template").html()
      );

      // Initialize counters
      this.sectionCount = 0;
      this.faqCount = 0;
      this.videoCount = 0;
      this.breadcrumbCount = 0;
    },

    loadCategories: function () {
      var self = this;

      $.ajax({
        url: restUrl + "/categories",
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (categories) {
          var $dropdown = $("#category");

          // Clear existing options except "Select Category"
          $dropdown.find("option:not(:first)").remove();

          // Add categories to dropdown
          categories.forEach(function (category) {
            $dropdown.append(
              '<option value="' +
                category.id +
                '">' +
                category.name +
                "</option>"
            );
          });
        },
      });
    },

    bindEvents: function () {
      var self = this;

      $("#author-image-button").on("click", function () {
        self.openMediaUploader(
          "#author-image",
          "#author-image-preview",
          "#author-image-remove"
        );
      });

      $("#author-image-remove").on("click", function () {
        $("#author-image").val("");
        $("#author-image-preview").attr("src", "").hide();
        $(this).hide();
      });

      // Add section button
      $("#add-section").on("click", function () {
        self.addSection();
      });

      // Add FAQ button
      $("#add-faq").on("click", function () {
        self.addFaq();
      });

      // Add video button
      $("#add-video").on("click", function () {
        self.addVideo();
      });

      // Add breadcrumb button
      $("#add-breadcrumb").on("click", function () {
        self.addBreadcrumb();
      });

      // Delete section button
      $(document).on("click", ".delete-section", function () {
        $(this).closest(".section-item").remove();
      });

      // Delete FAQ button
      $(document).on("click", ".delete-faq", function () {
        $(this).closest(".faq-item").remove();
      });

      // Delete video button
      $(document).on("click", ".delete-video", function () {
        $(this).closest(".video-item").remove();
      });

      // Delete breadcrumb button
      $(document).on("click", ".delete-breadcrumb", function () {
        $(this).closest(".breadcrumb-item").remove();
      });

      // Move up buttons
      $(document).on("click", ".move-up", function () {
        var $item = $(this).closest("[data-index]");
        var $prev = $item.prev("[data-index]");

        if ($prev.length) {
          $item.insertBefore($prev);
        }
      });

      // Move down buttons
      $(document).on("click", ".move-down", function () {
        var $item = $(this).closest("[data-index]");
        var $next = $item.next("[data-index]");

        if ($next.length) {
          $item.insertAfter($next);
        }
      });

      // Hero Image select
      $("#hero-image-button").on("click", function () {
        self.openMediaUploader(
          "#page-hero-image",
          "#hero-image-preview",
          "#hero-image-remove"
        );
      });

      // OG Image select
      $("#og-image-button").on("click", function () {
        self.openMediaUploader(
          "#page-og-image",
          "#og-image-preview",
          "#og-image-remove"
        );
      });

      // Remove hero image
      $("#hero-image-remove").on("click", function () {
        $("#page-hero-image").val("");
        $("#hero-image-preview").attr("src", "").hide();
        $(this).hide();
      });

      // Remove OG image
      $("#og-image-remove").on("click", function () {
        $("#page-og-image").val("");
        $("#og-image-preview").attr("src", "").hide();
        $(this).hide();
      });

      // Save page button
      $("#save-page").on("click", function () {
        self.savePage();
      });

      // Delete page button
      $("#delete-page-button").on("click", function (e) {
        e.preventDefault();

        if (
          confirm(
            "Are you sure you want to delete this page? This action cannot be undone."
          )
        ) {
          self.deletePage();
        }
      });

      // Preview page button
      $("#preview-page").on("click", function () {
        self.previewPage();
      });

      // Add social link button
      $("#add-social-link").on("click", function() {
        self.addSocialLink();
      });
    },

    loadPage: function (pageId) {
      var self = this;

      $.ajax({
        url: restUrl + "/pages/" + pageId,
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (page) {
          self.populateForm(page);
        },
        error: function (xhr) {
          alert("Error loading page: " + xhr.responseText);
        },
      });
    },

    populateForm: function (page) {
      var self = this;

      // Basic fields
      $("#page-slug").val(page.slug);
      $("#page-h1").val(page.h1);

      // If wp_editor exists for intro text
      if (typeof tinyMCE !== "undefined" && tinyMCE.get("page-intro-text")) {
        tinyMCE.get("page-intro-text").setContent(page.intro_text || "");
      } else {
        $("#page-intro-text").val(page.intro_text || "");
      }

      // If wp_editor exists for section text
      if (typeof tinyMCE !== "undefined") {
        var sectionEditor = tinyMCE.get("page-section-text");
        if (sectionEditor) {
          // Wait for editor to be fully initialized
          if (sectionEditor.initialized) {
            sectionEditor.setContent(page.section_text || "");
          } else {
            sectionEditor.on('init', function() {
              sectionEditor.setContent(page.section_text || "");
            });
          }
        } else {
          // Try to find the textarea and set it directly
          $("textarea[name='section_text']").val(page.section_text || "");
        }
      } else {
        $("textarea[name='section_text']").val(page.section_text || "");
      }

      // Hero banner text
      $("#page-hero-banner-text").val(page.hero_banner_text || "");

      // Hero image
      if (page.hero_image) {
        $("#page-hero-image").val(page.hero_image);
        $("#hero-image-preview").attr("src", page.hero_image).show();
        $("#hero-image-remove").show();
      }

      // Banner Section
      if (page.banner_heading) {
        $('#banner-heading').val(page.banner_heading);
        $('#banner-description').val(page.banner_description || '');
        $('#banner-service').val(page.banner_service || '');
      }

      // Conclusion section
      if (page.conclusion_heading) {
        $("#conclusion-heading").val(page.conclusion_heading);
        console.log("Set conclusion heading:", page.conclusion_heading);
      }

      // If wp_editor exists for conclusion content
      if (typeof tinyMCE !== "undefined") {
        var conclusionEditor = tinyMCE.get("conclusion-content");
        if (conclusionEditor) {
          conclusionEditor.setContent(page.conclusion_content || "");
          console.log(
            "Set conclusion_content from tinyMCE.get():",
            page.conclusion_content
          );
        } else if (tinyMCE.editors["conclusion-content"]) {
          tinyMCE.editors["conclusion-content"].setContent(
            page.conclusion_content || ""
          );
          console.log(
            "Set conclusion_content from tinyMCE.editors[]:",
            page.conclusion_content
          );
        } else {
          // Try to find the textarea and set it directly
          $("textarea[name='conclusion_content']").val(
            page.conclusion_content || ""
          );
          console.log(
            "Set conclusion_content from textarea:",
            page.conclusion_content
          );
        }
      } else {
        $("textarea[name='conclusion_content']").val(
          page.conclusion_content || ""
        );
        console.log(
          "Set conclusion_content from textarea (no TinyMCE):",
          page.conclusion_content
        );
      }

      // SEO fields
      $("#meta-title").val(page.meta_title || "");
      $("#meta-desc").val(page.meta_desc || "");
      $("#og-title").val(page.og_title || "");
      $("#og-desc").val(page.og_desc || "");

      if (page.og_image) {
        $("#page-og-image").val(page.og_image);
        $("#og-image-preview").attr("src", page.og_image).show();
        $("#og-image-remove").show();
      }

      $("#canonical").val(page.canonical || "");
      $("#robots").val(page.robots || "index, follow");
      $("#in-sitemap").prop("checked", page.in_sitemap !== false);

      // Status
      $("#post_status").val(page.status || "draft");

      // Dates
      if (page.created) {
        $("#created-date").text(this.formatDate(page.created));
      }

      if (page.updated) {
        $("#modified-date").text(this.formatDate(page.updated));
      }

      if (page.published) {
        $("#published-date").text(this.formatDate(page.published));
        $("#publish-info").show();
      }

      // Structuring
      $("#region").val(page.region || "");
      $("#service").val(page.service || "");
      $("#sub-service").val(page.sub_service || "");
      $("#content-type").val(page.content_type || "");
      $("#category").val(page.category_id || "");
      $("#in-header-menu").prop("checked", page.in_header_menu);

      // Author fields
      $("#author-name").val(page.author_name || "");
      $("#author-bio").val(page.author_bio || "");

      if (page.author_image) {
        $("#author-image").val(page.author_image);
        $("#author-image-preview").attr("src", page.author_image).show();
        $("#author-image-remove").show();
      }

      // Populate social links
      if (page.author_social_links && page.author_social_links.length) {
        $("#social-links-container").empty();
        $.each(page.author_social_links, function(index, socialLink) {
          self.addSocialLink();
          var currentIndex = self.socialLinkIndex - 1;
          $("#social-platform-" + currentIndex).val(socialLink.platform);
          $("#social-url-" + currentIndex).val(socialLink.url);
        });
      }

      // Sections
      if (page.sections && page.sections.length) {
        $("#sections-container").empty();
        // Reset section count to ensure proper indexing
        this.sectionCount = 0;
        // Parse sections if they're stored as a string
        var sections = typeof page.sections === 'string' ? JSON.parse(page.sections) : page.sections;
        $.each(sections, function (index, section) {
          self.addSection(section);
        });
      }

      // FAQs
      if (page.faq_items && page.faq_items.length) {
        $("#faq-container").empty();
        $.each(page.faq_items, function (index, faq) {
          self.addFaq(faq);
        });
      }

      // Set FAQ heading
      $("#faq-heading").val(page.faq_heading || "");

      // Videos
      if (page.video_components && page.video_components.length) {
        $("#video-container").empty();
        $.each(page.video_components, function (index, video) {
          self.addVideo(video);
        });
      }

      // Breadcrumbs
      if (page.breadcrumbs && page.breadcrumbs.length) {
        $("#breadcrumb-container").empty();
        $.each(page.breadcrumbs, function (index, breadcrumb) {
          self.addBreadcrumb(breadcrumb);
        });
      }

      // Load conclusion
      $('#conclusion-heading').val(page.conclusion_heading || '');
      if (page.conclusion_content) {
        tinyMCE.get('conclusion-content').setContent(page.conclusion_content);
      }
    },

    addSection: function (data) {
      var index = this.sectionCount++;
      var html = this.sectionTemplate({ index: index });
      $("#sections-container").append(html);

      // Set other section data if provided
      if (data) {
        $("#section-heading-" + index).val(data.heading || "");
        $("#section-anchor-" + index).val(data.anchor || "");
      }

      // Initialize TinyMCE for the new section
      if (typeof tinyMCE !== 'undefined') {
        // Remove any existing editor with the same ID to prevent conflicts
        if (tinyMCE.get('section-content-' + index)) {
          tinyMCE.get('section-content-' + index).remove();
        }

        // Initialize new editor with WordPress settings
        var init = _.extend({}, tinyMCEPreInit.mceInit['page-intro-text']); // Clone default WP editor settings
        init.selector = '#section-content-' + index;
        init.id = 'section-content-' + index;
        init.elements = 'section-content-' + index;
        init.body_class = 'section-content';
        init.setup = function(editor) {
          editor.on('init', function() {
            // Set content after editor is fully initialized
            if (data && data.content) {
              editor.setContent(data.content);
              // Force save after setting content
              editor.save();
            }
          });
          editor.on('change', function() {
            editor.save();
          });
        };
        tinyMCE.init(init);

        // Initialize quicktags
        if (typeof QTags !== 'undefined') {
          QTags.addButton('section_' + index, 'Section ' + index, function() {
            var editor = tinyMCE.get('section-content-' + index);
            if (editor) {
              editor.setContent(editor.getContent() + '<p>New section content</p>');
            }
          });
        }
      }

      // Initialize section handlers
      this.initializeSectionHandlers(index);
    },

    initializeSectionHandlers: function(index) {
      var self = this;
      var $section = $('.section-item[data-index="' + index + '"]');

      // Move up button
      $section.find('.move-up').on('click', function() {
        var $prev = $section.prev('.section-item');
        if ($prev.length) {
          $section.insertBefore($prev);
          self.updateSectionNumbers();
        }
      });

      // Move down button
      $section.find('.move-down').on('click', function() {
        var $next = $section.next('.section-item');
        if ($next.length) {
          $section.insertAfter($next);
          self.updateSectionNumbers();
        }
      });

      // Delete button
      $section.find('.delete-section').on('click', function() {
        if (confirm('Are you sure you want to delete this section?')) {
          var editor = tinyMCE.get('section-content-' + index);
          if (editor) {
            editor.remove();
          }
          $section.remove();
          self.updateSectionNumbers();
        }
      });
    },

    updateSectionNumbers: function() {
      var self = this;
      $('.section-item').each(function(index) {
        var $section = $(this);
        var newIndex = index + 1;
        
        // Update data-index
        $section.attr('data-index', newIndex);
        
        // Update heading
        $section.find('h3').text('Section ' + newIndex);
        
        // Update input IDs and names
        $section.find('input, textarea').each(function() {
          var $input = $(this);
          var oldId = $input.attr('id');
          var oldName = $input.attr('name');
          
          if (oldId) {
            $input.attr('id', oldId.replace(/section-\d+/, 'section-' + newIndex));
          }
          if (oldName) {
            $input.attr('name', oldName.replace(/sections\[\d+\]/, 'sections[' + newIndex + ']'));
          }
        });

        // Update TinyMCE editor
        var oldEditorId = 'section-content-' + $section.data('index');
        var newEditorId = 'section-content-' + newIndex;
        
        if (tinyMCE.get(oldEditorId)) {
          var editor = tinyMCE.get(oldEditorId);
          var content = editor.getContent();
          editor.remove();
          
          // Reinitialize editor with new ID
          var init = _.extend({}, tinyMCEPreInit.mceInit['page-intro-text']);
          init.selector = '#' + newEditorId;
          init.id = newEditorId;
          init.elements = newEditorId;
          init.body_class = 'section-content';
          init.setup = function(editor) {
            editor.on('init', function() {
              // Set content after editor is fully initialized
              editor.setContent(content);
            });
            editor.on('change', function() {
              editor.save();
            });
          };
          tinyMCE.init(init);
        }
      });
    },

    addFaq: function (data) {
      var index = this.faqCount++;
      var html = this.faqTemplate({ index: index });
      $("#faq-container").append(html);

      // Fill with data if provided
      if (data) {
        $("#faq-question-" + index).val(data.question || "");
      }

      // Always initialize TinyMCE for the new FAQ answer
      if (typeof tinyMCE !== 'undefined') {
        if (tinyMCE.get('faq-answer-' + index)) {
          tinyMCE.get('faq-answer-' + index).remove();
        }

        var init = _.extend({}, tinyMCEPreInit.mceInit['page-intro-text']); // Clone default WP editor settings
        init.selector = '#faq-answer-' + index;
        init.id = 'faq-answer-' + index;
        init.elements = 'faq-answer-' + index;
        init.body_class = 'faq-answer';
        init.setup = function(editor) {
          editor.on('init', function() {
            // Set content after editor is fully initialized
            if (data && data.answer) {
              editor.setContent(data.answer);
              // Force save after setting content
              editor.save();
            }
          });
          editor.on('change', function() {
            editor.save();
          });
        };
        tinyMCE.init(init);
      }

      return index;
    },

    addVideo: function (data) {
      var index = this.videoCount++;
      var html = this.videoTemplate({ index: index });
      $("#video-container").append(html);

      // Fill with data if provided
      if (data) {
        $("#video-heading-" + index).val(data.heading || "");
        $("#video-text-" + index).val(data.text || "");
        $("#video-embed-" + index).val(data.embed || "");
      }

      return index;
    },

    addBreadcrumb: function (data) {
      var index = this.breadcrumbCount++;
      var html = this.breadcrumbTemplate({ index: index });
      $("#breadcrumb-container").append(html);

      // Fill with data if provided
      if (data) {
        $("#breadcrumb-text-" + index).val(data.text || "");
        $("#breadcrumb-url-" + index).val(data.url || "");
      }

      return index;
    },

    addSocialLink: function() {
      var template = $("#social-link-template").html();
      var html = template.replace(/{{index}}/g, this.socialLinkIndex++);
      $("#social-links-container").append(html);
      this.initializeSocialLinkHandlers($("#social-links-container").children().last());
    },

    initializeSocialLinkHandlers: function(element) {
      var $element = $(element);
      
      // Move up button
      $element.find('.move-up').on('click', function() {
        var $prev = $element.prev();
        if ($prev.length) {
          $element.insertBefore($prev);
        }
      });

      // Move down button
      $element.find('.move-down').on('click', function() {
        var $next = $element.next();
        if ($next.length) {
          $element.insertAfter($next);
        }
      });

      // Delete button
      $element.find('.delete-social-link').on('click', function() {
        $element.remove();
      });
    },

    openMediaUploader: function (
      inputSelector,
      previewSelector,
      removeButtonSelector
    ) {
      var mediaUploader;
      var $input = $(inputSelector);
      var $preview = $(previewSelector);
      var $removeButton = $(removeButtonSelector);

      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      mediaUploader = wp.media({
        title: "Select or Upload Image",
        button: {
          text: "Use this image",
        },
        multiple: false,
      });

      mediaUploader.on("select", function () {
        var attachment = mediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();
        $input.val(attachment.url);
        $preview.attr("src", attachment.url).show();
        $removeButton.show();
      });

      mediaUploader.open();
    },

    collectFormData: function () {
      var formData = {};

      // Basic info
      formData.slug = $("#page-slug").val();
      formData.h1 = $("#page-h1").val();

      // Get intro text - multiple approaches to be sure
      if (typeof tinyMCE !== "undefined") {
        // First try getting editor by ID
        var introEditor = tinyMCE.get("page-intro-text");
        if (introEditor) {
          formData.intro_text = introEditor.getContent();
          console.log(
            "Got intro_text from tinyMCE.get():",
            formData.intro_text
          );
        }
        // Then try getting by name when ID doesn't work
        else {
          formData.intro_text = $("textarea[name='intro_text']").val();
          console.log("Got intro_text from textarea:", formData.intro_text);
        }

        // Get section text
        var sectionEditor = tinyMCE.get("page-section-text");
        if (sectionEditor) {
          formData.section_text = sectionEditor.getContent();
          console.log(
            "Got section_text from tinyMCE.get():",
            formData.section_text
          );
        } else {
          formData.section_text = $("textarea[name='section_text']").val();
          console.log("Got section_text from textarea:", formData.section_text);
        }
      } else {
        formData.intro_text = $("textarea[name='intro_text']").val();
        formData.section_text = $("textarea[name='section_text']").val();
      }

      formData.hero_banner_text = $("#page-hero-banner-text").val();
      formData.hero_image = $("#page-hero-image").val();

      // Banner Section
      formData.banner_heading = $('#banner-heading').val();
      formData.banner_description = $('#banner-description').val();
      formData.banner_service = $('#banner-service').val();

      // FAQ Section
      formData.faq_heading = $("#faq-heading").val();

      // Conclusion section - use multiple approaches to ensure we get the content
      formData.conclusion_heading = $("#conclusion-heading").val();
      console.log("Conclusion heading:", formData.conclusion_heading);

      // First check if we can get the editor directly
      if (typeof tinyMCE !== "undefined") {
        // Try multiple ways to get the editor - first by ID
        var conclusionEditor = tinyMCE.get("conclusion-content");
        if (conclusionEditor) {
          formData.conclusion_content = conclusionEditor.getContent();
          console.log(
            "Got conclusion_content from tinyMCE.get():",
            formData.conclusion_content
          );
        }
        // Try from editors array if ID method failed
        else if (tinyMCE.editors["conclusion-content"]) {
          formData.conclusion_content =
            tinyMCE.editors["conclusion-content"].getContent();
          console.log(
            "Got conclusion_content from tinyMCE.editors[]:",
            formData.conclusion_content
          );
        }
        // Finally try directly from textarea by name
        else {
          formData.conclusion_content = $(
            "textarea[name='conclusion_content']"
          ).val();
          console.log(
            "Got conclusion_content from textarea by name:",
            formData.conclusion_content
          );
        }
      } else {
        // No TinyMCE, use the textarea directly
        formData.conclusion_content = $(
          "textarea[name='conclusion_content']"
        ).val();
        console.log(
          "TinyMCE not available, using textarea value for conclusion:",
          formData.conclusion_content
        );
      }

      // If still empty, make one last attempt using a broader selector
      if (!formData.conclusion_content) {
        formData.conclusion_content =
          $("textarea[id^='conclusion-content']").val() || "";
        console.log(
          "Final attempt to get conclusion_content:",
          formData.conclusion_content
        );
      }

      // SEO fields
      formData.meta_title = $("#meta-title").val();
      formData.meta_desc = $("#meta-desc").val();
      formData.og_title = $("#og-title").val();
      formData.og_desc = $("#og-desc").val();
      formData.og_image = $("#page-og-image").val();
      formData.canonical = $("#canonical").val();
      formData.robots = $("#robots").val();
      formData.in_sitemap = $("#in-sitemap").is(":checked");

      // Status
      formData.status = $("#post_status").val();

      // Structuring
      formData.region = $("#region").val();
      formData.service = $("#service").val();
      formData.sub_service = $("#sub-service").val();
      formData.content_type = $("#content-type").val();
      formData.category_id = $("#category").val() ? parseInt($("#category").val()) : null;
      formData.in_header_menu = $("#in-header-menu").is(":checked");

      // Author fields
      formData.author_name = $("#author-name").val();
      formData.author_bio = $("#author-bio").val();
      formData.author_image = $("#author-image").val();

      // Collect social links
      formData.author_social_links = [];
      $(".social-link-item").each(function() {
        var index = $(this).data("index");
        var socialLink = {
          platform: $("#social-platform-" + index).val(),
          url: $("#social-url-" + index).val()
        };
        formData.author_social_links.push(socialLink);
      });

      // Page ID if editing
      if (this.pageId) {
        formData.id = this.pageId;
      }

      // Sections
      formData.sections = [];
      $(".section-item").each(function () {
        var index = $(this).data("index");
        var editor = tinyMCE.get('section-content-' + index);
        var section = {
          heading: $("#section-heading-" + index).val(),
          content: editor ? editor.getContent() : $("#section-content-" + index).val(),
          anchor: $("#section-anchor-" + index).val()
        };
        formData.sections.push(section);
      });

      // FAQs
      formData.faq_items = [];
      $(".faq-item").each(function () {
        var index = $(this).data("index");
        var editor = tinyMCE.get('faq-answer-' + index);
        var faq = {
          question: $("#faq-question-" + index).val(),
          answer: editor ? editor.getContent() : $("#faq-answer-" + index).val(),
        };
        formData.faq_items.push(faq);
      });

      // Videos
      formData.video_components = [];
      $(".video-item").each(function () {
        var index = $(this).data("index");
        var video = {
          heading: $("#video-heading-" + index).val(),
          text: $("#video-text-" + index).val(),
          embed: $("#video-embed-" + index).val(),
        };
        formData.video_components.push(video);
      });

      // Breadcrumbs
      formData.breadcrumbs = [];
      $(".breadcrumb-item").each(function () {
        var index = $(this).data("index");
        var breadcrumb = {
          text: $("#breadcrumb-text-" + index).val(),
          url: $("#breadcrumb-url-" + index).val(),
        };
        formData.breadcrumbs.push(breadcrumb);
      });

      // Final log of the form data
      console.log("Complete form data being sent:", formData);

      return formData;
    },

    savePage: function () {
      var self = this;

      // Force all TinyMCE editors to update their textareas
      if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
      }

      // Now collect the form data after ensuring textareas are updated
      var formData = this.collectFormData();

      $.ajax({
        url: restUrl + "/pages",
        method: "POST",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: formData,
        success: function (page) {
          console.log("Page saved successfully:", page);

          if (!self.pageId) {
            // Redirect to edit page if this was a new page
            window.location.href =
              "admin.php?page=custom-cms-new&id=" + page.id + "&saved=1";
          } else {
            alert("Page saved successfully!");
            // Update the page ID and timestamps
            $("#created-date").text(self.formatDate(page.created));
            $("#modified-date").text(self.formatDate(page.updated));

            if (page.published) {
              $("#published-date").text(self.formatDate(page.published));
              $("#publish-info").show();
            }
          }
        },
        error: function (xhr) {
          console.error("Error saving page:", xhr.responseText);
          // Check for duplicate slug error
          if (xhr.responseText && (xhr.responseText.toLowerCase().includes('duplicate entry') || xhr.responseText.toLowerCase().includes('slug'))) {
            alert("Warning: The slug you entered already exists. Please choose a unique slug.");
          } else {
            alert("Error saving page: " + xhr.responseText);
          }
        },
      });
    },

    deletePage: function () {
      // Remove all TinyMCE editors before deleting the page
      if (typeof tinyMCE !== 'undefined') {
        $('.section-item').each(function() {
          var index = $(this).data('index');
          if (tinyMCE.get('section-content-' + index)) {
            tinyMCE.get('section-content-' + index).remove();
          }
        });
      }

      var pageId = this.pageId;

      $.ajax({
        url: restUrl + "/pages/" + pageId,
        method: "DELETE",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function () {
          window.location.href = "admin.php?page=custom-cms&deleted=1";
        },
        error: function (xhr) {
          alert("Error deleting page: " + xhr.responseText);
        },
      });
    },

    previewPage: function () {
      var self = this;

      // Force TinyMCE to update textareas before collecting data
      if (typeof tinyMCE !== "undefined") {
        tinyMCE.triggerSave();
      }

      // Save the page first
      var formData = this.collectFormData();

      $.ajax({
        url: restUrl + "/pages",
        method: "POST",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: formData,
        success: function (page) {
          // Open preview URL in a new tab
          var previewUrl = "/?preview=1&custom_page=" + page.slug;
          window.open(previewUrl, "_blank");
        },
        error: function (xhr) {
          alert("Error saving page for preview: " + xhr.responseText);
        },
      });
    },

    formatDate: function (dateString) {
      if (!dateString) return "";

      var date = new Date(dateString);
      return date.toLocaleString();
    },
  };

  /**
   * Enhanced Categories List functionality - Fixed Category Creation
   */
  var CategoriesList = {
    init: function () {
      if (!$("#category-list-container").length) {
        return;
      }

      this.setupTemplates();
      this.loadCategories();
      this.bindEvents();

      // Add debugging for REST API URL and nonce
      console.log("REST API URL:", restUrl);
      console.log("REST Nonce exists:", restNonce ? "Yes" : "No");
    },

    setupTemplates: function () {
      // Make sure the template exists before compiling
      var templateElement = $("#category-item-template");
      if (templateElement.length) {
        var source = templateElement.html();
        this.template = Handlebars.compile(source);
      } else {
        console.error("Category item template not found");
      }
    },

    loadCategories: function () {
      var self = this;

      $.ajax({
        url: restUrl + "/categories",
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (data) {
          console.log("Categories loaded:", data);
          self.renderCategories(data);
          // Load categories into parent dropdown
          self.populateParentDropdown(data);
        },
        error: function (xhr, status, error) {
          console.error("Error loading categories:", xhr.responseText);
          var errorMsg = "Error loading categories: " + error;

          // Display error in the notification area
          $("#category-notification")
            .removeClass()
            .addClass("notice notice-error")
            .html("<p>" + errorMsg + "</p>")
            .show();

          setTimeout(function () {
            $("#category-notification").hide();
          }, 5000);
        },
      });
    },

    renderCategories: function (categories) {
      var self = this;
      var $list = $("#the-category-list");

      $list.empty();

      if (categories.length === 0) {
        $list.html(
          '<tr class="no-items"><td class="colspanchange" colspan="5">No categories found.</td></tr>'
        );
        return;
      }

      // Build a map of categories by ID for parent lookup
      var categoryMap = {};
      categories.forEach(function (category) {
        categoryMap[category.id] = category;
      });

      // Render each category
      categories.forEach(function (category) {
        // Add parent name if there's a parent
        category.parent_name =
          category.parent_id && categoryMap[category.parent_id]
            ? categoryMap[category.parent_id].name
            : "";

        // Truncate description if too long
        if (category.description && category.description.length > 50) {
          category.description = category.description.substring(0, 50) + "...";
        }

        $list.append(self.template(category));
      });
    },

    populateParentDropdown: function (categories) {
      var $dropdown = $("#category-parent");

      // Clear existing options except "None"
      $dropdown.find("option:not(:first)").remove();

      // Add categories to dropdown
      categories.forEach(function (category) {
        $dropdown.append(
          '<option value="' + category.id + '">' + category.name + "</option>"
        );
      });
    },

    bindEvents: function () {
      var self = this;

      // Open modal to add new category
      $("#add-new-category").on("click", function (e) {
        e.preventDefault();
        console.log("Add new category button clicked");
        $("#category-modal-title").text("Add New Category");
        $("#category-form")[0].reset();
        $("#category-id").val("");
        $("#category-modal").css("display", "block");
      });

      // Close modal
      $(".csp-close, #cancel-category").on("click", function () {
        $("#category-modal").css("display", "none");
      });

      // Close on click outside modal
      $(window).on("click", function (e) {
        if ($(e.target).is(".csp-modal")) {
          $("#category-modal").css("display", "none");
        }
      });

      // Edit category
      $(document).on("click", ".edit-category", function (e) {
        e.preventDefault();
        var categoryId = $(this).data("id");
        self.loadCategory(categoryId);
      });

      // Delete category
      $(document).on("click", ".delete-category", function (e) {
        e.preventDefault();
        var categoryId = $(this).data("id");

        if (
          confirm(
            "Are you sure you want to delete this category? This action cannot be undone."
          )
        ) {
          self.deleteCategory(categoryId);
        }
      });

      // Save category - Form submit event
      $("#category-form").on("submit", function (e) {
        e.preventDefault();
        console.log("Form submitted");
        self.saveCategory();
      });

      // Auto-generate slug from name
      $("#category-name").on("blur", function () {
        if ($("#category-slug").val() === "") {
          var name = $(this).val();
          var slug = name
            .toLowerCase()
            .replace(/[^a-z0-9 -]/g, "") // Remove invalid characters
            .replace(/\s+/g, "-") // Replace spaces with -
            .replace(/-+/g, "-"); // Replace multiple - with single -

          $("#category-slug").val(slug);
        }
      });
    },

    loadCategory: function (categoryId) {
      var self = this;

      $.ajax({
        url: restUrl + "/categories/" + categoryId,
        method: "GET",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (category) {
          console.log("Category loaded:", category);
          $("#category-modal-title").text("Edit Category");
          $("#category-id").val(category.id);
          $("#category-name").val(category.name);
          $("#category-slug").val(category.slug);
          $("#category-description").val(category.description);
          $("#category-parent").val(category.parent_id || "");

          $("#category-modal").css("display", "block");
        },
        error: function (xhr, status, error) {
          console.error("Error loading category:", xhr.responseText);
          var errorMsg = "Error loading category: " + error;

          // Display error in the notification area
          $("#category-notification")
            .removeClass()
            .addClass("notice notice-error")
            .html("<p>" + errorMsg + "</p>")
            .show();

          setTimeout(function () {
            $("#category-notification").hide();
          }, 5000);
        },
      });
    },

    saveCategory: function () {
      var self = this;

      // Collect form data
      var categoryName = $("#category-name").val();
      var categorySlug = $("#category-slug").val();
      var categoryDescription = $("#category-description").val();
      var categoryParentId = $("#category-parent").val() || null;
      var categoryId = $("#category-id").val() || null;

      // Debug log form data
      console.log("Form data collected:", {
        name: categoryName,
        slug: categorySlug,
        description: categoryDescription,
        parent_id: categoryParentId,
        id: categoryId,
      });

      // Validate required fields
      if (!categoryName) {
        alert("Category name is required.");
        return;
      }

      if (!categorySlug) {
        alert("Category slug is required.");
        return;
      }

      // Prepare form data
      var formData = {
        name: categoryName,
        slug: categorySlug,
      };

      // Only add non-empty fields
      if (categoryDescription) {
        formData.description = categoryDescription;
      }

      if (categoryParentId) {
        formData.parent_id = categoryParentId;
      }

      if (categoryId) {
        formData.id = categoryId;
      }

      // Debug log actual data being sent
      console.log("Sending category data to API:", formData);
      console.log("API URL:", restUrl + "/categories");

      // Use jQuery AJAX with proper content type for form data
      $.ajax({
        url: restUrl + "/categories",
        method: "POST",
        contentType: "application/x-www-form-urlencoded",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        data: formData,
        success: function (response) {
          console.log("Category saved successfully:", response);

          // Display success message
          $("#category-notification")
            .removeClass()
            .addClass("notice notice-success is-dismissible")
            .html("<p>Category saved successfully.</p>")
            .show();

          // Hide after 3 seconds
          setTimeout(function () {
            $("#category-notification").hide();
          }, 3000);

          // Close modal
          $("#category-modal").css("display", "none");

          // Reload categories
          self.loadCategories();
        },
        error: function (xhr, status, error) {
          console.error("Error saving category:", xhr.responseText);
          var errorMsg = "Error saving category: " + error;

          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg += ": " + xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              var responseObj = JSON.parse(xhr.responseText);
              if (responseObj.message) {
                errorMsg += ": " + responseObj.message;
              }
            } catch (e) {
              errorMsg += ": " + xhr.responseText;
            }
          }

          alert(errorMsg);
        },
      });
    },

    deleteCategory: function (categoryId) {
      var self = this;

      $.ajax({
        url: restUrl + "/categories/" + categoryId,
        method: "DELETE",
        beforeSend: function (xhr) {
          xhr.setRequestHeader("X-WP-Nonce", restNonce);
        },
        success: function (response) {
          console.log("Category deleted successfully:", response);

          // Display success message
          $("#category-notification")
            .removeClass()
            .addClass("notice notice-success is-dismissible")
            .html("<p>Category deleted successfully.</p>")
            .show();

          // Hide after 3 seconds
          setTimeout(function () {
            $("#category-notification").hide();
          }, 3000);

          self.loadCategories();
        },
        error: function (xhr, status, error) {
          console.error("Error deleting category:", xhr.responseText);
          var errorMsg = "Error deleting category: " + error;

          // Display error in the notification area
          $("#category-notification")
            .removeClass()
            .addClass("notice notice-error")
            .html("<p>" + errorMsg + "</p>")
            .show();

          setTimeout(function () {
            $("#category-notification").hide();
          }, 5000);
        },
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    PagesList.init();
    PageEditor.init();
    CategoriesList.init();
  });
})(jQuery);
