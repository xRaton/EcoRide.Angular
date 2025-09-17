import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private baseUrl = 'http://127.0.0.1:8080/api';

  constructor(private http: HttpClient) {}

  register(user: {pseudo: string, email: string, password: string}): Observable<any> {
    return this.http.post(`${this.baseUrl}/register`, user);
  }

  login(credentials: {email: string, password: string}): Observable<any> {
    return this.http.post(`${this.baseUrl}/login`, credentials);
  }

  getCurrentUser(): Observable<any> {
    return this.http.get(`${this.baseUrl}/me`);
  }

  getAllUsers(): Observable<any> {
    return this.http.get(`${this.baseUrl}/users`);
  }

  // ------------------ VEHICLES ------------------
  addVehicle(vehicle: {
    immatriculation: string,
    marque: string,
    modele: string,
    couleur: string,
    energie: string,
    seats: number,
    date_premiere_immat: string
  }): Observable<any> {
    return this.http.post(`${this.baseUrl}/vehicles`, vehicle);
  }

  getMyVehicles(): Observable<any> {
    return this.http.get(`${this.baseUrl}/vehicles/me`);
  }

  deleteVehicle(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/vehicles/${id}`);
  }

  // ------------------ RIDES ------------------
  getAllRides(): Observable<any> {
    return this.http.get(`${this.baseUrl}/rides`);
  }

  getRide(id: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/rides/${id}`);
  }

  createRide(ride: {
    vehicle_id: number,
    from_city: string,
    to_city: string,
    departure_time: string,
    seats: number,
    price: number
  }): Observable<any> {
    return this.http.post(`${this.baseUrl}/rides`, ride);
  }

  cancelRide(id: number): Observable<any> {
    return this.http.put(`${this.baseUrl}/rides/${id}/cancel`, {});
  }

  // ------------------ BOOKINGS ------------------
  getMyBookings(): Observable<any> {
    return this.http.get(`${this.baseUrl}/bookings/me`);
  }

  bookRide(data: {
    ride_id: number,
    seats_booked: number
  }): Observable<any> {
    return this.http.post(`${this.baseUrl}/bookings`, data);
  }

  cancelBooking(id: number): Observable<any> {
    return this.http.put(`${this.baseUrl}/bookings/${id}/cancel`, {});
  }

  // ------------------ REVIEWS ------------------
  getReviewsForUser(userId: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/reviews/user/${userId}`);
  }

  postReview(data: {
    ride_id: number,
    target_user_id: number,
    note: number,
    commentaire: string
  }): Observable<any> {
    return this.http.post(`${this.baseUrl}/reviews`, data);
  }

  // ------------------ TRANSACTIONS ------------------
  getMyTransactions(): Observable<any> {
    return this.http.get(`${this.baseUrl}/transactions/me`);
  }
}

