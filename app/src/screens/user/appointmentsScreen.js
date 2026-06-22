import React, { useState, useEffect, useRef } from 'react';
import { 
    ScrollView, View, Text, StyleSheet, TouchableOpacity, 
    Alert, ActivityIndicator, Platform 
} from 'react-native';
import DropDownPicker from 'react-native-dropdown-picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import AsyncStorage from '@react-native-async-storage/async-storage'; 

export default function AppointmentsScreen() {
    const scrollViewRef = useRef(null);
    
    const [loading, setLoading] = useState(true);
    const [appointments, setAppointments] = useState([]);
    const [pets, setPets] = useState([]);
    const [vets, setVets] = useState([]);

    const [selectedPet, setSelectedPet] = useState(null);
    const [selectedVet, setSelectedVet] = useState(null);
    const [date, setDate] = useState(new Date()); 
    const [editingId, setEditingId] = useState(null);
    const [showPicker, setShowPicker] = useState(false);

    const [openPet, setOpenPet] = useState(false);
    const [openVet, setOpenVet] = useState(false);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=list_owner_appointments&api_token=${token}`);
            const data = await res.json();
            
            if (data.success) {
                setAppointments(data.appointments);
                setPets(data.pets.map(p => ({ label: p.name, value: p.id })));
                setVets(data.vets.map(v => ({ label: `Dr. ${v.first_name} ${v.last_name}`, value: v.id })));
            } else {
                Alert.alert("Error", data.error || "Failed to load data");
            }
        } catch (err) {
            Alert.alert("Error", "Could not connect to server");
        } finally {
            setLoading(false);
        }
    };

    const canCancel = (appointmentTime) => {
        const now = new Date();
        const appDate = new Date(appointmentTime.replace(/-/g, '/'));
        const diffInMs = appDate - now;
        return diffInMs > 3600000; 
    };

    const handleEditSelect = (item) => {
        setEditingId(item.id);
        setSelectedPet(item.pet_id);
        setSelectedVet(item.vet_id);
        const appDate = new Date(item.appointment_time.replace(/-/g, '/'));
        setDate(isNaN(appDate) ? new Date() : appDate);
        scrollViewRef.current?.scrollTo({ y: 0, animated: true });
    };

    const handleDelete = (id) => {
        Alert.alert("Cancel Appointment", "Are you sure you want to delete this booking?", [
            { text: "No", style: "cancel" },
            { 
                text: "Yes, Delete", 
                style: "destructive", 
                onPress: async () => {
                    try {
                        const token = await AsyncStorage.getItem('user_token');
                        const response = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=delete&api_token=${token}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ appointment_id: id })
                        });
                        const res = await response.json();
                        if (res.success) {
                            Alert.alert("Success", "Appointment removed.");
                            fetchData();
                        } else {
                            Alert.alert("Error", res.error);
                        }
                    } catch (e) {
                        Alert.alert("Error", "Network error");
                    }
                }
            }
        ]);
    };

    const resetForm = () => {
        setEditingId(null);
        setSelectedPet(null);
        setSelectedVet(null);
        setDate(new Date());
    };

    const formatDate = (d) => {
        if (!d) return "";
        return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2) + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
    };

    const handleSave = async () => {
        if (!selectedPet || !selectedVet) {
            Alert.alert("Error", "Fill all fields!");
            return;
        }

        try {
            const token = await AsyncStorage.getItem('user_token');
            const action = editingId ? 'update' : 'add';
            const response = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=${action}&api_token=${token}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    pet_id: selectedPet,
                    vet_id: selectedVet,
                    appointment_date: formatDate(date),
                    appointment_id: editingId
                })
            });
            const resData = await response.json();
            if (resData.success) {
                Alert.alert("Success", editingId ? "Updated!" : "Booked!");
                resetForm();
                fetchData();
            } else {
                Alert.alert("Error", resData.error || "Save failed");
            }
        } catch (e) {
            Alert.alert("Error", "Network error");
        }
    };

    if (loading) return <ActivityIndicator size="large" color="#4CAF50" style={{ flex: 1 }} />;

    return (
        <ScrollView 
            ref={scrollViewRef} 
            style={styles.container} 
            nestedScrollEnabled={true}
        >
            <Text style={styles.title}>{editingId ? "Edit Appointment" : "Book Appointment"}</Text>
            
            <View style={[styles.card, editingId && styles.editingCard]}>
                <Text style={styles.label}>Pet</Text>
                <DropDownPicker
                    open={openPet} value={selectedPet} items={pets}
                    setOpen={setOpenPet} setValue={setSelectedPet}
                    placeholder="Select Pet" listMode="SCROLLVIEW"
                    style={styles.dropdown} zIndex={3000}
                    selectedItemContainerStyle={{backgroundColor: "#E8F5E9"}}
                    tickIconStyle={{tintColor: "#4CAF50"}}
                />

                <Text style={styles.label}>Vet</Text>
                <DropDownPicker
                    open={openVet} value={selectedVet} items={vets}
                    setOpen={setOpenVet} setValue={setSelectedVet}
                    placeholder="Select Vet" listMode="SCROLLVIEW"
                    style={styles.dropdown} zIndex={2000}
                    selectedItemContainerStyle={{backgroundColor: "#E8F5E9"}}
                    tickIconStyle={{tintColor: "#4CAF50"}}
                />

                <Text style={styles.label}>Date & Time</Text>
                <TouchableOpacity style={styles.dateOpenButton} onPress={() => setShowPicker(true)}>
                    <Text style={{color: '#2E7D32', fontWeight: '500'}}>{formatDate(date)}</Text>
                </TouchableOpacity>

                {(showPicker || Platform.OS === 'ios') && (
                    <DateTimePicker
                        value={date} 
                        mode="datetime" 
                        is24Hour={true}
                        display={Platform.OS === 'ios' ? 'inline' : 'default'}
                        accentColor="#4CAF50"
                        textColor="#2E7D32"
                        onChange={(e, d) => { 
                            setShowPicker(Platform.OS === 'ios'); 
                            if(d) setDate(d); 
                        }}
                    />
                )}

                <View style={{ flexDirection: 'row', gap: 10, marginTop: 15 }}>
                    <TouchableOpacity style={[styles.saveButton, { flex: 2 }]} onPress={handleSave}>
                        <Text style={styles.saveButtonText}>{editingId ? "Update" : "Confirm"}</Text>
                    </TouchableOpacity>
                    
                    {editingId && (
                        <TouchableOpacity style={[styles.saveButton, { backgroundColor: '#757575', flex: 1 }]} onPress={resetForm}>
                            <Text style={styles.saveButtonText}>Cancel</Text>
                        </TouchableOpacity>
                    )}
                </View>
            </View>

            <Text style={styles.title}>Your Appointments</Text>
            {appointments.length > 0 ? (
                appointments.map((item) => {
                    const cancelable = canCancel(item.appointment_time);
                    return (
                        <View key={item.id} style={styles.appCard}>
                            <TouchableOpacity 
                                style={{ flex: 1 }} 
                                onPress={() => handleEditSelect(item)}
                            >
                                <Text style={styles.appName}>{item.pet_name} - Dr. {item.vet_first} {item.vet_last}</Text>
                                <Text style={styles.appTime}>{item.appointment_time}</Text>
                                {item.note && <Text style={styles.noteText}>Note: {item.note}</Text>}
                            </TouchableOpacity>
                            
                            <View style={styles.rightActions}>
                                {cancelable ? (
                                    <TouchableOpacity onPress={() => handleDelete(item.id)} style={styles.deleteBadge}>
                                        <Text style={styles.deleteText}>Cancel</Text>
                                    </TouchableOpacity>
                                ) : (
                                    <View style={[styles.editBadge, {backgroundColor: '#F5F5F5'}]}>
                                        <Text style={{color: '#9E9E9E', fontSize: 10, fontWeight: 'bold'}}>LOCKED</Text>
                                    </View>
                                )}
                                <TouchableOpacity onPress={() => handleEditSelect(item)} style={styles.editBadge}>
                                    <Text style={styles.editText}>Edit</Text>
                                </TouchableOpacity>
                            </View>
                        </View>
                    );
                })
            ) : (
                <Text style={{ textAlign: 'center', color: '#999', marginTop: 20 }}>No appointments found.</Text>
            )}
            <View style={{ height: 50 }} />
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, padding: 20, backgroundColor: '#F8FFF8', paddingTop: 40 },
    title: { fontSize: 20, fontWeight: 'bold', marginVertical: 10, color: '#2E7D32' },
    card: { backgroundColor: '#fff', padding: 15, borderRadius: 12, elevation: 3, marginBottom: 20, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 4 },
    editingCard: { borderColor: '#4CAF50', borderWidth: 2 },
    label: { fontWeight: 'bold', marginBottom: 5, color: '#555' },
    dropdown: { borderColor: '#C5D8C5', marginBottom: 15 },
    dateOpenButton: { padding: 12, borderWidth: 1, borderColor: '#4CAF50', borderRadius: 8, marginBottom: 10, backgroundColor: '#E8F5E9', alignItems: 'center' },
    saveButton: { backgroundColor: '#4CAF50', padding: 12, borderRadius: 8, alignItems: 'center' },
    saveButtonText: { color: '#fff', fontWeight: 'bold' },
    appCard: { 
        backgroundColor: '#fff', padding: 15, borderRadius: 10, marginBottom: 10, 
        flexDirection: 'row', alignItems: 'center', borderLeftWidth: 5, borderLeftColor: '#4CAF50', elevation: 2
    },
    appName: { fontWeight: 'bold', fontSize: 15, color: '#333' },
    appTime: { color: '#666', fontSize: 13, marginTop: 4 },
    noteText: { color: '#D32F2F', fontSize: 12, fontStyle: 'italic', marginTop: 2 },
    rightActions: { alignItems: 'flex-end', gap: 5 },
    editBadge: { backgroundColor: '#E8F5E9', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 5 },
    deleteBadge: { backgroundColor: '#FFEBEE', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 5 },
    editText: { color: '#4CAF50', fontWeight: 'bold', fontSize: 11 },
    deleteText: { color: '#D32F2F', fontWeight: 'bold', fontSize: 11 }
});