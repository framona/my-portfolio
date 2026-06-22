import React, { useState, useCallback, useMemo } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ScrollView, ActivityIndicator, Alert, SafeAreaView, Modal, TextInput } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function ManageUsers({ navigation }) {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editModal, setEditModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');

    const fetchUsers = async () => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=list_users&api_token=${token}`);
            const data = await res.json();
            if (data.success) {
                setUsers(data.users || []);
            }
        } catch (err) {
            Alert.alert("Error", "Could not load users.");
        } finally {
            setLoading(false);
        }
    };

    useFocusEffect(
        useCallback(() => {
            fetchUsers();
        }, [])
    );

    const filteredUsers = useMemo(() => {
        if (!searchTerm.trim()) return users;
        
        const term = searchTerm.toLowerCase();
        return users.filter(u => {
            const firstName = u.first_name?.toLowerCase() || '';
            const lastName = u.last_name?.toLowerCase() || '';
            const email = u.email?.toLowerCase() || '';
            
            return firstName.includes(term) || 
                   lastName.includes(term) || 
                   email.includes(term);
        });
    }, [searchTerm, users]);

    const handleToggle = async (id) => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=toggle_user&id=${id}&api_token=${token}`);
            const data = await res.json();
            if (data.success) fetchUsers();
        } catch (err) {
            Alert.alert("Error", "Failed to update status.");
        }
    };

    const handleDelete = (id) => {
        Alert.alert("Delete", "Are you sure? All related data will be removed.", [
            { text: "Cancel", style: "cancel" },
            { 
                text: "Delete", 
                style: "destructive", 
                onPress: async () => {
                    try {
                        const token = await AsyncStorage.getItem('user_token');
                        const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=delete_user&id=${id}&api_token=${token}`);
                        const data = await res.json();
                        if (data.success) {
                            Alert.alert("Success", "User deleted.");
                            fetchUsers();
                        } else {
                            Alert.alert("Error", data.error || "Deletion failed.");
                        }
                    } catch (err) {
                        Alert.alert("Error", "Network error while deleting.");
                    }
                }
            }
        ]);
    };

    const handleUpdate = async () => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=update_user&api_token=${token}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(selectedUser)
            });

            const data = await res.json();
            if (data.success) {
                Alert.alert("Success", "User updated successfully.");
                setEditModal(false);
                fetchUsers();
            } else {
                Alert.alert("Error", data.error || "Update failed.");
            }
        } catch (err) {
            Alert.alert("Error", "Network error while updating.");
        }
    };

    if (loading) return (
        <View style={styles.center}>
            <ActivityIndicator size="large" color="#4CAF50" />
        </View>
    );

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.container}>
                <Text style={styles.title}>Manage Users</Text>
                
                <View style={styles.searchContainer}>
                    <TextInput
                        style={styles.searchInput}
                        placeholder="Search by name or email..."
                        value={searchTerm}
                        onChangeText={(text) => setSearchTerm(text)}
                        clearButtonMode="while-editing" 
                    />
                </View>

                <ScrollView showsVerticalScrollIndicator={false}>
                    {filteredUsers.length > 0 ? (
                        filteredUsers.map(u => (
                            <View key={u.id} style={styles.userCard}>
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.userName}>{u.first_name} {u.last_name}</Text>
                                    <Text style={styles.userEmail}>{u.email}</Text>
                                    <View style={styles.badgeRow}>
                                        <Text style={styles.roleBadge}>{u.role.toUpperCase()}</Text>
                                        <Text style={[styles.statusBadge, { backgroundColor: u.is_active == 1 ? '#4CAF50' : '#FF5252' }]}>
                                            {u.is_active == 1 ? 'Active' : 'Disabled'}
                                        </Text>
                                    </View>
                                </View>
                                
                                {u.role !== 'admin' && (
                                    <View style={styles.actions}>
                                        <TouchableOpacity onPress={() => handleToggle(u.id)} style={styles.actionBtn}>
                                            <Text style={{fontSize: 18}}>{u.is_active == 1 ? '🚫' : '✅'}</Text>
                                        </TouchableOpacity>
                                        <TouchableOpacity onPress={() => { setSelectedUser(u); setEditModal(true); }} style={styles.actionBtn}>
                                            <Text style={{fontSize: 18}}>✏️</Text>
                                        </TouchableOpacity>
                                        <TouchableOpacity onPress={() => handleDelete(u.id)} style={styles.actionBtn}>
                                            <Text style={{fontSize: 18}}>🗑️</Text>
                                        </TouchableOpacity>
                                    </View>
                                )}
                            </View>
                        ))
                    ) : (
                        <Text style={styles.noResult}>No users found.</Text>
                    )}
                </ScrollView>
            </View>

            <Modal visible={editModal} animationType="fade" transparent>
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <Text style={styles.modalTitle}>Edit User Details</Text>
                        
                        <Text style={styles.inputLabel}>First Name</Text>
                        <TextInput style={styles.input} value={selectedUser?.first_name} onChangeText={t => setSelectedUser({...selectedUser, first_name: t})} />
                        
                        <Text style={styles.inputLabel}>Last Name</Text>
                        <TextInput style={styles.input} value={selectedUser?.last_name} onChangeText={t => setSelectedUser({...selectedUser, last_name: t})} />
                        
                        <Text style={styles.inputLabel}>Email</Text>
                        <TextInput style={styles.input} value={selectedUser?.email} onChangeText={t => setSelectedUser({...selectedUser, email: t})} keyboardType="email-address" autoCapitalize="none" />
                        
                        <Text style={styles.inputLabel}>Phone</Text>
                        <TextInput style={styles.input} value={selectedUser?.phone} onChangeText={t => setSelectedUser({...selectedUser, phone: t})} keyboardType="phone-pad" />
                        
                        <View style={styles.modalBtns}>
                            <TouchableOpacity style={styles.saveBtn} onPress={handleUpdate}>
                                <Text style={styles.saveBtnText}>Save Changes</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={styles.cancelBtn} onPress={() => setEditModal(false)}>
                                <Text style={styles.cancelBtnText}>Cancel</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeArea: { flex: 1, backgroundColor: '#F8FFF8' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F8FFF8' },
    container: { padding: 20, flex: 1 },
    title: { fontSize: 24, fontWeight: 'bold', color: '#2E7D32', marginBottom: 15 },
    searchContainer: { marginBottom: 20 },
    searchInput: { backgroundColor: '#fff', padding: 12, borderRadius: 10, borderWidth: 1, borderColor: '#E0E0E0', fontSize: 16, elevation: 2, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 2 },
    userCard: { backgroundColor: '#fff', padding: 16, borderRadius: 14, marginBottom: 12, flexDirection: 'row', justifyContent: 'space-between', elevation: 4, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 4 },
    userName: { fontSize: 17, fontWeight: 'bold', color: '#2E7D32' },
    userEmail: { fontSize: 14, color: '#666', marginVertical: 2 },
    badgeRow: { flexDirection: 'row', gap: 6, marginTop: 8 },
    roleBadge: { backgroundColor: '#E8F5E9', color: '#2E7D32', fontSize: 11, paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, fontWeight: '600', overflow: 'hidden' },
    statusBadge: { color: '#fff', fontSize: 11, paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6, fontWeight: '600', overflow: 'hidden' },
    actions: { flexDirection: 'row', alignItems: 'center', gap: 12, marginLeft: 10 },
    actionBtn: { padding: 4 },
    noResult: { textAlign: 'center', marginTop: 20, color: '#888', fontSize: 16 },
    modalOverlay: { flex: 1, justifyContent: 'center', backgroundColor: 'rgba(0,0,0,0.6)', padding: 20 },
    modalContent: { backgroundColor: '#fff', padding: 24, borderRadius: 20 },
    modalTitle: { fontSize: 20, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
    inputLabel: { fontSize: 13, fontWeight: '600', color: '#777', marginBottom: 4 },
    input: { borderWidth: 1, borderColor: '#E0E0E0', padding: 12, borderRadius: 10, marginBottom: 16, backgroundColor: '#FAFAFA' },
    modalBtns: { flexDirection: 'column', gap: 10 },
    saveBtn: { backgroundColor: '#4CAF50', padding: 14, borderRadius: 10, alignItems: 'center' },
    saveBtnText: { color: '#fff', fontWeight: 'bold' },
    cancelBtn: { padding: 14, borderRadius: 10, alignItems: 'center' },
    cancelBtnText: { color: '#888', fontWeight: '600' }
});