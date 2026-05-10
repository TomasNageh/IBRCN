function displayPassword() {
  var password = document.getElementById("password");
  var displayPass = document.getElementById("display-pass");
  var hidenPass = document.getElementById("hiden-pass");

  if (!password || !displayPass || !hidenPass) return;

  if (password.type === "password") {
    password.type = "text";
    displayPass.style.display = "block";
    hidenPass.style.display = "none";
  } else {
    password.type = "password";
    displayPass.style.display = "none";
    hidenPass.style.display = "block";
  }
}

function displayPasswordConfirm() {
  var passConfirm = document.getElementById("passwordConfirm");
  var displayPassConfirm = document.getElementById("display-passConfirm");
  var hidenPassConfirm = document.getElementById("hiden-passConfirm");

  if (!passConfirm || !displayPassConfirm || !hidenPassConfirm) return;

  if (passConfirm.type === "password") {
    passConfirm.type = "text";
    displayPassConfirm.style.display = "block";
    hidenPassConfirm.style.display = "none";
  } else {
    passConfirm.type = "password";
    displayPassConfirm.style.display = "none";
    hidenPassConfirm.style.display = "block";
  }
}

(function attachGlobalChromeHandlers() {
  var searchFormEl = document.querySelector(".header .search-form");
  var searchBtnToggle = document.querySelector("#search-btn");

  if (searchBtnToggle && searchFormEl) {
    searchBtnToggle.onclick = function () {
      searchFormEl.classList.toggle("active");
    };
  }

  var navLinks = document.querySelectorAll("header .navbar a");
  var sections = document.querySelectorAll("section[id]");

  window.onscroll = function () {
    if (searchFormEl && typeof document.activeElement !== "undefined") {
      searchFormEl.classList.remove("active");
    }

    if (navLinks.length && sections.length) {
      sections.forEach(function (sec) {
        var top = window.scrollY;
        var height = sec.offsetHeight;
        var offset = sec.offsetTop - 150;
        var id = sec.getAttribute("id");

        if (!id) return;

        if (top >= offset && top < offset + height) {
          navLinks.forEach(function (links) {
            links.classList.remove("active");
          });
          var activeLink =
            document.querySelector('header .navbar a[href="#' + id + '"]') ||
            document.querySelector('header .navbar a[href*="#' + id + '"]');
          if (activeLink) activeLink.classList.add("active");
        }
      });
    }

    var header2 = document.querySelector(".header .header-2");
    if (header2) {
      if (window.scrollY > 80) header2.classList.add("active");
      else header2.classList.remove("active");
    }
  };
})();

function loader() {
  var lc = document.querySelector(".loader-container");
  if (lc) lc.classList.add("active");
}

function fadeOutLoader() {
  var lcQuery = document.querySelector(".loader-container");
  if (!lcQuery) return;
  setTimeout(loader, 4000);
}

window.onload = function () {
  var header2 = document.querySelector(".header .header-2");
  if (header2) {
    if (window.scrollY > 80) header2.classList.add("active");
    else header2.classList.remove("active");
  }
  fadeOutLoader();
};

(function attachCarousels() {
  if (typeof Swiper === "undefined") return;

  function initSwiper(sel, opts) {
    if (!document.querySelector(sel)) return;
    return new Swiper(sel, opts);
  }

  initSwiper(".books-slider", {
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 3000, disableOnInteraction: false },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });

  initSwiper(".populer-slider", {
    spaceBetween: 10,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 5000, disableOnInteraction: false },
    navigation: {
      nextEl: ".populer .swiper-button-next",
      prevEl: ".populer .swiper-button-prev",
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      450: { slidesPerView: 2 },
      768: { slidesPerView: 3 },
      1024: { slidesPerView: 4 },
    },
  });

  initSwiper(".new-slider", {
    spaceBetween: 10,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 3500, disableOnInteraction: false },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });

  initSwiper(".new-slider-2", {
    spaceBetween: 10,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 6000, disableOnInteraction: false },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });

  initSwiper(".reviews-slider", {
    spaceBetween: 10,
    grabCursor: true,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 33500, disableOnInteraction: false },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
    },
  });
})();

(function attachBlogLoadMore() {
  var loadMoreBtn = document.querySelector("#load-more");
  if (!loadMoreBtn) return;

  var currentItem = 3;

  loadMoreBtn.onclick = function () {
    var boxes = [].slice.call(
      document.querySelectorAll(".container .box-container .box"),
    );
    for (var i = currentItem; i < currentItem + 3 && i < boxes.length; i++) {
      boxes[i].style.display = "inline-block";
    }
    currentItem += 3;
    if (currentItem >= boxes.length) {
      loadMoreBtn.style.display = "none";
    }
  };
})();

(function initReaderHome() {
  var popSlider = document.querySelector(".populer-slider");
  if (!popSlider) return;

  var storageUserId =
    parseInt(
      document.documentElement.getAttribute("data-user-id") || "0",
      10,
    ) || 0;
  var prevStorageUser = parseInt(
    sessionStorage.getItem("ibrcn_ls_user") || "0",
    10,
  );
  if (storageUserId > 0 && prevStorageUser > 0 && storageUserId !== prevStorageUser) {
    try {
      localStorage.removeItem("cart");
      localStorage.removeItem("ibrcn_wishlist");
    } catch (e) {}
  }
  if (storageUserId > 0) {
    sessionStorage.setItem("ibrcn_ls_user", String(storageUserId));
  }

  var WISHLIST_KEY =
    storageUserId > 0
      ? "ibrcn_wishlist_u" + storageUserId
      : "ibrcn_wishlist";
  var CART_LS_KEY =
    storageUserId > 0 ? "ibrcn_cart_u" + storageUserId : "cart";

  function debounce(fn, ms) {
    var t = null;
    return function () {
      var ctx = this,
        args = arguments;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(ctx, args);
      }, ms);
    };
  }

  var searchInput = document.getElementById("search-box");
  var searchForm = document.getElementById("reader-search-form");
  var searchSubmit = document.getElementById("reader-search-submit");
  var resultsSection = document.getElementById("search-results-section");
  var resultsGrid = document.getElementById("search-results-grid");
  var resultsEmpty = document.getElementById("search-results-empty");
  var resultsStatus = document.getElementById("search-results-status");
  var wishlistGrid = document.getElementById("wishlist-grid");
  var wishlistEmpty = document.getElementById("wishlist-empty");

  function readWishlist() {
    try {
      return JSON.parse(localStorage.getItem(WISHLIST_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  function writeWishlist(items) {
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(items));
  }

  function readCart() {
    try {
      return JSON.parse(localStorage.getItem(CART_LS_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  function writeCart(cart) {
    localStorage.setItem(CART_LS_KEY, JSON.stringify(cart));
  }

  function normalizeCartItem(item) {
    return {
      book_id: item.bookId ? Number(item.bookId) : Number(item.book_id || 0),
      title: item.name || item.title || "",
      name: item.name || item.title || "",
      author: item.author || "",
      image: item.image || item.cover_image || "",
      unitPrice: Number(item.unitPrice || item.price_value || 0),
      price:
        item.price ||
        (Number(item.unitPrice || item.price_value || 0) > 0
          ? "EGP " + Number(item.unitPrice || item.price_value || 0).toFixed(2)
          : ""),
      quantity: Math.max(1, Number(item.quantity || 1)),
    };
  }

  function syncCartToServer(cart) {
    return fetch("cart-sync.php", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ cart: cart }),
    }).then(function (response) {
      return response.ok ? response.json() : Promise.reject(response);
    });
  }

  function addOrMergeCartItem(item) {
    var normalized = normalizeCartItem(item);
    var cart = readCart();
    var found = cart.find(function (x) {
      return String(x.book_id || x.bookId || 0) === String(normalized.book_id);
    });

    if (found) {
      found.quantity = Number(found.quantity || 1) + normalized.quantity;
      found.title = normalized.title || found.title;
      found.name = normalized.name || found.name;
      found.author = normalized.author || found.author;
      found.image = normalized.image || found.image;
      found.unitPrice = normalized.unitPrice || found.unitPrice;
      found.price = normalized.price || found.price;
    } else {
      cart.push(normalized);
    }

    writeCart(cart);
    return syncCartToServer(cart).catch(function () {
      return { ok: false };
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function renderBookCardHtml(book) {
    var title = escapeHtml(book.title);
    var author = escapeHtml(book.author || "");
    var priceDisp = escapeHtml(book.price_egp_formatted || "");
    var priceValue = Number(book.price_value || 0);
    var img = escapeHtml(book.cover_image || "");
    var id = parseInt(book.book_id, 10) || 0;
    return (
      '<div class="swiper-slide box">' +
      '<div class="icons">' +
      '<a href="#" class="fas fa-search reader-prefill-search" aria-label="Search this title" data-book-title="' +
      title +
      '"></a>' +
      '<a href="#" class="fas fa-heart-circle-plus reader-wishlist-toggle" aria-label="Wishlist"' +
      ' data-book-id="' +
      id +
      '"' +
      ' data-book-title="' +
      title +
      '"' +
      ' data-book-price="' +
      priceDisp +
      '"' +
      ' data-book-price-value="' +
      escapeHtml(String(priceValue)) +
      '"' +
      ' data-book-img="' +
      img +
      '"' +
      ' data-book-author="' +
      author +
      '"></a>' +
      '<a href="#" class="fas fa-info reader-book-info" aria-label="Book info"' +
      ' data-book-title="' +
      title +
      '"' +
      ' data-book-author="' +
      author +
      '"' +
      ' data-book-price="' +
      priceDisp +
      '"></a>' +
      "</div>" +
      '<div class="image">' +
      '<img src="' +
      img +
      '" alt="" />' +
      "</div>" +
      '<div class="content">' +
      "<h3>" +
      title +
      "</h3>" +
      '<div class="price">' +
      priceDisp +
      "</div>" +
      '<a href="book-stores.php?book_id=' +
      encodeURIComponent(id) +
      '" class="reader-book-stores"' +
      ' style="display:block;text-align:center;font-size:1.25rem;margin:0.4rem 0 0.75rem;color:#27ae60;text-decoration:none;">Pickup locations</a>' +
      '<a href="#populer" class="btn reader-add-cart"' +
      ' data-book-id="' +
      id +
      '"' +
      ' data-book-title="' +
      title +
      '"' +
      ' data-book-price="' +
      priceDisp +
      '"' +
      ' data-book-price-value="' +
      escapeHtml(String(priceValue)) +
      '"' +
      ' data-book-author="' +
      author +
      '"' +
      ' data-book-img="' +
      img +
      '"' +
      ">Add To Cart</a>" +
      "</div>" +
      "</div>"
    );
  }

  function renderWishlistCardHtml(book) {
    var title = escapeHtml(book.title);
    var author = escapeHtml(book.author || "");
    var priceDisp = escapeHtml(book.price_egp_formatted || book.price || "");
    var priceValue = Number(book.price_value || 0);
    var img = escapeHtml(book.cover_image || "");
    var id = parseInt(book.book_id, 10) || 0;
    return (
      '<article class="wishlist-card">' +
      '<div class="wishlist-card__image">' +
      '<img src="' +
      img +
      '" alt="" />' +
      "</div>" +
      '<div class="wishlist-card__content">' +
      '<h3 class="wishlist-card__title">' +
      title +
      "</h3>" +
      '<div class="wishlist-card__meta">' +
      (author ? author : "Saved item") +
      "</div>" +
      '<div class="wishlist-card__price">' +
      priceDisp +
      "</div>" +
      '<div class="wishlist-card__actions">' +
      '<a href="#populer" class="btn reader-add-cart"' +
      ' data-book-id="' +
      id +
      '"' +
      ' data-book-title="' +
      title +
      '"' +
      ' data-book-price="' +
      priceDisp +
      '"' +
      ' data-book-price-value="' +
      escapeHtml(String(priceValue)) +
      '"' +
      ' data-book-author="' +
      author +
      '"' +
      ' data-book-img="' +
      img +
      '"' +
      ">Add To Cart</a>" +
      '<button type="button" class="wishlist-remove-btn reader-wishlist-remove"' +
      ' data-book-id="' +
      id +
      '"' +
      ' data-book-title="' +
      title +
      '">Remove</button>' +
      "</div>" +
      "</div>" +
      "</article>"
    );
  }

  function runSearch(forceEmptyMessage) {
    var q = (searchInput && searchInput.value.trim()) || "";
    if (!resultsSection || !resultsGrid) return;

    if (resultsStatus) resultsStatus.style.display = "none";
    if (resultsEmpty) resultsEmpty.style.display = "none";
    resultsGrid.innerHTML = "";

    if (!q) {
      if (forceEmptyMessage !== true) resultsSection.style.display = "none";
      return;
    }

    fetch("search-books.php?q=" + encodeURIComponent(q), {
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.ok ? response.json() : Promise.reject(response);
      })
      .then(function (data) {
        var books = (data && data.books) || [];
        resultsSection.style.display = "block";

        if (books.length === 0) {
          if (resultsEmpty) resultsEmpty.style.display = "block";
          resultsSection.scrollIntoView({ behavior: "smooth", block: "start" });
          return;
        }

        if (resultsEmpty) resultsEmpty.style.display = "none";

        books.forEach(function (b) {
          resultsGrid.insertAdjacentHTML("beforeend", renderBookCardHtml(b));
        });
        resultsSection.scrollIntoView({ behavior: "smooth", block: "start" });
      })
      .catch(function () {
        if (resultsStatus) {
          resultsStatus.textContent = "Search failed. Try again.";
          resultsStatus.style.display = "block";
        }
        resultsSection.style.display = "block";
      });
  }

  var debouncedSearch = debounce(function () {
    if (!searchInput) return;
    var q = searchInput.value.trim();
    if (q.length >= 2) runSearch();
    else if (resultsSection && q.length === 0) {
      resultsSection.style.display = "none";
      if (resultsEmpty) resultsEmpty.style.display = "none";
      if (resultsGrid) resultsGrid.innerHTML = "";
    }
  }, 350);

  if (searchInput) {
    searchInput.addEventListener("input", debouncedSearch);
    searchInput.addEventListener("keyup", function (e) {
      if (e.key === "Enter") {
        runSearch(true);
      }
    });
  }

  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();
      runSearch(true);
    });
  }

  if (searchSubmit) {
    searchSubmit.addEventListener("click", function (e) {
      e.preventDefault();
      runSearch(true);
    });
  }

  function renderWishlist() {
    if (!wishlistGrid || !wishlistEmpty) return;
    var items = readWishlist();
    wishlistGrid.innerHTML = "";
    if (!items.length) {
      wishlistEmpty.style.display = "block";
      return;
    }
    wishlistEmpty.style.display = "none";
    items.forEach(function (wi) {
      var cardHtml = renderWishlistCardHtml({
        book_id: wi.id,
        title: wi.title,
        author: wi.author,
        cover_image: wi.image,
        price_value: wi.priceValue,
        price_egp_formatted: wi.price,
      });
      wishlistGrid.insertAdjacentHTML("beforeend", cardHtml);
    });
  }

  function removeWishlistItem(btn) {
    var id = btn.getAttribute("data-book-id") || "";
    var title = btn.getAttribute("data-book-title") || "";
    var list = readWishlist();
    var existing = list.findIndex(function (x) {
      return (id && String(x.id) === String(id)) || (!id && x.title === title);
    });
    if (existing >= 0) {
      list.splice(existing, 1);
      writeWishlist(list);
      renderWishlist();
    }
  }

  renderWishlist();

  function toggleWishlistFromEl(btn) {
    var id = btn.getAttribute("data-book-id") || "";
    var title = btn.getAttribute("data-book-title") || "";
    var price = btn.getAttribute("data-book-price") || "";
    var priceValue = btn.getAttribute("data-book-price-value") || "";
    var image = btn.getAttribute("data-book-img") || "";
    var author = btn.getAttribute("data-book-author") || "";
    var list = readWishlist();
    var key = id ? "id-" + id : "t-" + title;
    var existing = list.findIndex(function (x) {
      return (id && String(x.id) === String(id)) || (!id && x.title === title);
    });
    if (existing >= 0) {
      list.splice(existing, 1);
      writeWishlist(list);
      alert("Removed from wishlist.");
    } else {
      list.push({
        id: id,
        title: title,
        price: price,
        priceValue: priceValue,
        image: image,
        author: author,
      });
      writeWishlist(list);
      alert("Saved to wishlist!");
    }
    renderWishlist();
  }

  document.body.addEventListener("click", function (e) {
    var tgt = e.target;

    var wishlistRemove = tgt.closest(".reader-wishlist-remove");
    if (wishlistRemove) {
      e.preventDefault();
      e.stopPropagation();
      removeWishlistItem(wishlistRemove);
      return;
    }

    var addCart = tgt.closest(".reader-add-cart");
    if (addCart) {
      e.preventDefault();
      e.stopPropagation();
      var slide = addCart.closest(".swiper-slide");
      var bookId = "";
      var name = "";
      var priceTxt = "";
      var priceValue = NaN;
      var imgSrc = "";
      var author = "";
      if (addCart.dataset.bookId || addCart.dataset.bookTitle) {
        bookId = addCart.dataset.bookId || "";
        name = addCart.dataset.bookTitle;
        priceTxt = addCart.dataset.bookPrice || "";
        priceValue = Number(addCart.dataset.bookPriceValue);
        imgSrc = addCart.dataset.bookImg || "";
        author = addCart.dataset.bookAuthor || "";
      } else if (slide) {
        var h3El = slide.querySelector("h3");
        var pEl = slide.querySelector(".price");
        var im = slide.querySelector("img");
        name = h3El ? h3El.textContent.trim() : "";
        priceTxt = pEl ? pEl.textContent.trim() : "";
        imgSrc = im ? im.getAttribute("src") : "";
      }

      if (!isFinite(priceValue)) {
        var m = String(priceTxt).match(/(\d+(?:\.\d+)?)/);
        priceValue = m ? Number(m[1]) : NaN;
      }
      if (isFinite(priceValue)) priceTxt = "EGP " + priceValue.toFixed(2);

      if (name && isFinite(priceValue) && imgSrc) {
        addOrMergeCartItem({
          bookId: bookId || null,
          name: name,
          unitPrice: priceValue,
          price: priceTxt,
          image: imgSrc,
          author: author,
        }).then(function () {
          alert("Product added to cart!");
          window.location.href = "cart.php";
        });
      }
      return;
    }

    var infoBtn = tgt.closest(".reader-book-info");
    if (infoBtn) {
      e.preventDefault();
      e.stopPropagation();
      var t = infoBtn.getAttribute("data-book-title") || "";
      var a = infoBtn.getAttribute("data-book-author") || "";
      var p = infoBtn.getAttribute("data-book-price") || "";
      alert(t + (a ? "\nAuthor: " + a : "") + (p ? "\nPrice: " + p : ""));
      return;
    }

    var wl = tgt.closest(".reader-wishlist-toggle");
    if (wl) {
      e.preventDefault();
      e.stopPropagation();
      toggleWishlistFromEl(wl);
      return;
    }

    var pre = tgt.closest(".reader-prefill-search");
    if (pre) {
      e.preventDefault();
      e.stopPropagation();
      var title = pre.getAttribute("data-book-title") || "";
      if (searchInput) {
        searchInput.value = title;
        runSearch(true);
        var hdrSearch = document.querySelector(
          ".header .header-1 .search-form",
        );
        if (hdrSearch) hdrSearch.classList.add("active");
      }
      return;
    }
  });
})();
