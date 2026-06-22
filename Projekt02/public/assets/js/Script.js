'use strict';

const toggleButton = document.getElementsByClassName('toggle-button')[0];
const navbarLinks = document.getElementsByClassName('navbar-links')[0];

toggleButton.addEventListener('click', () => {
    navbarLinks.classList.toggle('active');
    toggleButton.classList.toggle('active');
});

document.querySelectorAll('.navbar-links a').forEach(link => {
    link.addEventListener('click', () => {
        navbarLinks.classList.remove('active');
        toggleButton.classList.remove('active');
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const sortByPopularityBtn = document.getElementById('sortByPopularity');
    const resultsContainer = document.getElementById('workoutResults');
    const defaultCards = document.querySelectorAll('.defaultResults > .col-md-6');

    let sortByPopularity = false;

    function fetchPlans(query = '') {
        let url = `fetch_workouts.php?search=${encodeURIComponent(query)}`;
        if (sortByPopularity) {
            url += '&sort=popularity';
        }

        fetch(url)
            .then(response => response.json())
            .then(plans => {
                defaultCards.forEach(card => card.style.display = 'none');
                resultsContainer.innerHTML = '';

                if (plans.length === 0) {
                    resultsContainer.innerHTML = '<div class="alert alert-warning">No results found.</div>';
                    return;
                }

                plans.forEach(plan => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6';
                    const categoryClass = `category-${plan.category_name.toLowerCase().replace(/\s+/g, '-')}`;
                    card.innerHTML = `
                            <div class="card">
                                <div class="card-header ${categoryClass}">${plan.plan_name}</div>
                                <div class="card-body">
                                    <p><strong>Category:</strong> ${plan.category_name}</p>
                                    <p><strong>Trainer:</strong> ${plan.first_name} ${plan.last_name}</p>
                                    <p><strong>Added on:</strong> ${plan.created_at.split(' ')[0]}</p>
                                    <p><strong>Popularity:</strong> ${plan.popularity}</p>
                                    <a href="view_workout.php?plan=${plan.id}" class="btn btn-outline-dark me-2">Details</a>
                                    ${userId ? `
                                        <form action="process_enroll.php" method="POST" class="d-inline">
                                            <input type="hidden" name="plan_id" value="${plan.id}">
                                            <button type="submit" class="btn btn-enroll">Enroll</button>
                                        </form>
                                    ` : `<a href="login.php" class="btn btn-enroll">Log in to Enroll</a>`}
                                </div>
                            </div>`;
                    resultsContainer.appendChild(card);
                });
            })
            .catch(error => {
                console.error('Error during fetch:', error);
                resultsContainer.innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
            });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.trim();
            if (query === '') {
                defaultCards.forEach(card => card.style.display = 'block');
                resultsContainer.innerHTML = '';
                return;
            }
            fetchPlans(query);
        });
    }

    if (sortByPopularityBtn) {
        sortByPopularityBtn.addEventListener('click', function () {
            sortByPopularity = !sortByPopularity;
            fetchPlans(searchInput.value.trim());
        });
    }
});
