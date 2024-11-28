document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('reservation-modal');
    const modalContent = document.getElementById('reservation-details');
    const closeModalButton = document.querySelector('.close-modal');

    // Handle row click
    document.querySelectorAll('.reservation-row').forEach(row => {
        row.addEventListener('click', () => {
            const reservationId = row.getAttribute('data-reservation-id');
            console.log(`Fetching details for reservation ID: ${reservationId}`);

            // Fetch reservation details via AJAX
            fetch(`${ajax_object.ajax_url}?action=get_reservation_details&id=${reservationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const details = data.data;
                        modalContent.innerHTML = `
                                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
        <a href="${ajax_object.admin_url}post.php?post=${reservationId}&action=edit" 
           class="button button-primary" 
           style="display: flex; align-items: center; gap: 5px;">
            <span class="dashicons dashicons-edit"></span> Labot
        </a>
        <a href="#" class="button button-secondary pdf-button" 
           data-id="${reservationId}" 
           style="display: flex; align-items: center; gap: 5px;">
            <span class="dashicons dashicons-media-document"></span> Skatīt PDF
        </a>
    </div>
                            <h1>${details.event_title || 'Nav nosaukuma'}</h1>
                            <h3>Rezervācijas informācija</h3>
                            <p><strong>Telpa:</strong> ${details.room_name}</p>
                            <p><strong>Organizācija:</strong> ${details.organizer_name}</p>
                            <p><strong>Juridiskā adrese:</strong> ${details.organizer_address}</p>
                            <p><strong>Reģistrācijas numurs:</strong> ${details.organizer_reg_number}</p>
                            <p><strong>Dalībnieku skaits:</strong> ${details.participant_count}</p>
                            <p><strong>Pasākuma biežums:</strong> ${details.event_frequency}</p>
                            <p><strong>Pasākuma veids:</strong> ${details.event_type}</p>
                            <p><strong>Personas ar kustību traucējumiem:</strong> ${details.disability_friendly}</p>
                            <p><strong>Pasākuma apraksts:</strong> ${details.event_description}</p>
                            <p><strong>Pieprasītais aprīkojums:</strong> ${details.equipment_requested.join(', ') || 'Nav atzīmes'}</p>
                            <p><strong>Kafijas pauzes:</strong> ${details.coffee_breaks}</p>
                            <p><strong>Kontaktpersona:</strong> ${details.contact_name}</p>
                            <p><strong>Tālrunis:</strong> ${details.contact_phone}</p>
                            <p><strong>E-pasts:</strong> ${details.contact_email}</p>
                            <p><strong>Paraksttiesīgā persona:</strong> ${details.signatory_name}</p>
                            <p><strong>Amats:</strong> ${details.signatory_position}</p>
                            <p><strong>Statuss:</strong> ${details.status}</p>
                            <p><strong>Datumi:</strong></p>
                            <ul>
                                ${details.dates.map(date => `<li>${date.date} No plkst.: ${date.from} Līdz plkst.: ${date.to}</li>`).join('')}
                            </ul>
                        `;
                    } else {
                        modalContent.innerHTML = `<p>${data.message || 'Neizdevās ielādēt rezervācijas informāciju.'}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching reservation details:', error);
                    modalContent.innerHTML = `<p>Neizdevās ielādēt rezervācijas informāciju.</p>`;
                });

            // Show modal
            modal.classList.remove('hidden');
        });
    });

    // Close modal
    closeModalButton.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Close modal on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
	
	//PDF generation
	modal.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('pdf-button')) {
            e.preventDefault();
            const reservationId = e.target.getAttribute('data-id');
            if (reservationId) {
                window.location.href = ajax_object.admin_url + `?action=generate_reservation_pdf&id=${reservationId}`;
            }
        }
    });
});
