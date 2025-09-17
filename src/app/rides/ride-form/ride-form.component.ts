import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-ride-form',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl:'./ride-form.component.html'
})
export class RideFormComponent implements OnInit {
  vehicles: any[] = [];   // pour afficher les véhicules de l'utilisateur
  ride = {
    vehicle_id: 0,
    from_city: '',
    to_city: '',
    departure_time: '',
    seats: 1,
    price: 5
  };

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    // Charger les véhicules de l'utilisateur pour qu'il puisse en choisir un
    this.api.getMyVehicles().subscribe(res => this.vehicles = res);
  }

  createRide() {
    if (!this.ride.vehicle_id) {
      alert('Veuillez sélectionner un véhicule');
      return;
    }

    this.api.createRide(this.ride).subscribe({
      next: () => {
        alert('Trajet créé avec succès ');
        this.ride = {
          vehicle_id: 0,
          from_city: '',
          to_city: '',
          departure_time: '',
          seats: 1,
          price: 5
        };
      },
      error: err => alert('Erreur : ' + err.message)
    });
  }
}

