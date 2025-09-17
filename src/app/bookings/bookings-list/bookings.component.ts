import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-book-ride',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './bookings.component.html'
})
export class BookingsListComponent implements OnInit {
  rides: any[] = [];
  booking = {
    ride_id: 0,
    seats_booked: 1
  };

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    // Charger les trajets disponibles
    this.api.getAllRides().subscribe(res => this.rides = res);
  }

  bookRide() {
    if (!this.booking.ride_id) {
      alert('Veuillez sélectionner un trajet');
      return;
    }

    this.api.bookRide(this.booking).subscribe({
      next: () => {
        alert('Réservation effectuée ');
        this.booking = { ride_id: 0, seats_booked: 1 };
      },
      error: err => alert('Erreur : ' + err.message)
    });
  }
}


